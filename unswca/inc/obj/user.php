<?php

class User {
    var $zid;
    var $given_name;
    var $family_name;
    var $uoc;
    var $wam;
    var $program;
    var $streams;
    var $courses;
    var $all_courses;

    public function __construct($zid, $all_courses) {
        include("inc/pgsql.php");
        include("program_taken.php");
        include("stream_taken.php");
        include("course_taken.php");
        include("requirement.php");

        $query = "SELECT * FROM people WHERE id = $zid";
        $result = pg_query($sims_db_connection, $query);
        $rows = pg_fetch_array($result);
        $zid = str_ireplace(" ", "", $rows["id"]);
        $given_name = $rows["given_name"];
        $family_name = $rows["family_name"];

        // Construct ProgramTaken object
        $result = $this->getTypeInfo($zid, "program");
        $rows = pg_fetch_array($result);
        $code = str_ireplace(" ", "", $rows["code"]);
        $title = $rows["title"];
        $career = str_ireplace(" ", "", $rows["career"]);
        $pr_uoc = str_ireplace(" ", "", $rows["uoc"]);

        // Check the school and faculty responsible for the program
        $school_faculty = pg_fetch_array(getSchoolAndFaculty($code));
        $school = $school_faculty["school"];
        $faculty = $school_faculty["faculty"];
        $requirements = $this->getRequirements($code, $career, $all_courses);

        $program = new ProgramTaken($code, $title, $career, $pr_uoc, $school, $faculty, $requirements);

        // Construct StreamTaken object
        $i = 0;
        $streams = array();
        $result = $this->getTypeInfo($zid, "stream");
        while ($rows = pg_fetch_array($result)) {
            $code = str_ireplace(" ", "", $rows["code"]);
            $title = $rows["title"];
            $career = str_ireplace(" ", "", $rows["career"]);
            $st_uoc = str_ireplace(" ", "", $rows["uoc"]);

            // Check the school and faculty responsible for the stream
            $school_faculty = pg_fetch_array(getSchoolAndFaculty($code));
            $school = $school_faculty["school"];
            $faculty = $school_faculty["faculty"];
            $requirements = $this->getRequirements($code, $program->getCareer(), $all_courses);

            $stream = new StreamTaken($code, $title, $career, $st_uoc, $school, $faculty, $requirements);
            $streams[$i++] = $stream;
        }

        // Construct CourseTaken object
        $i = 0;
        $courses = array();
        $numerator = 0;     // Numerator of the UNSW WAM calculation
        $denominator = 0;   // Denominator of the UNSW WAM calculation
        $wam = 0;
        $uoc = 0;
        $result = $this->getCourseInfo($zid);
        while ($rows = pg_fetch_array($result)) {
            $code = str_ireplace(" ", "", $rows["code"]);
            $title = $rows["title"];
            $mark = str_ireplace(" ", "", $rows["mark"]);
            $grade = str_ireplace(" ", "", $rows["grade"]);
            $career = str_ireplace(" ", "", $rows["career"]);
            $s_uoc = str_ireplace(" ", "", $rows["uoc"]);
            $term = str_ireplace(" ", "", $rows["term"]);

            $course = new CourseTaken($code, $title, $mark, $grade, $career, $s_uoc, $term);
            $courses[$i++] = $course;

            // Calculate completed UOC and UNSW WAM
            // UNSW WAM = sigma($mark*$uoc)/sigma($uoc)
            if ($course->getGrade() == "SY") {          // i.e. COMP4930 (Thesis Part A)
                $uoc += $course->getUOC();
            } else if ($course->getOutcome() == 1) {    // Passed course
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
                $uoc += $course->getUOC();
            } else if ($course->getOutcome() == 2) {    // Failed course
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
            } else if ($course->getOutcome() == 3) {    // i.e. exchange, research course
                $uoc += $course->getUOC();
            }
        }

        if ($uoc == 0) {
            $wam = 0;
        } else {
            $wam = $numerator/$denominator;
            $wam = round($wam, 3);
        }

        $this->zid = $zid;
        $this->given_name = $given_name;
        $this->family_name = $family_name;
        $this->uoc = $uoc;
        $this->wam = $wam;
        $this->program = $program;
        $this->streams = $streams;
        $this->courses = $courses;
        $this->all_courses = $all_courses;
    }

    // Get the remaining CC (Core Courses) requirements of the program or stream taken
    // @return $remaining_requirements - array of remaining CC Requirement objects
    public function getRemainingCCRequirements() {
        include("inc/pgsql.php");
        $i = 0;
        $remaining_requirements = array();
        $remaining_defns = array();

        foreach ($this->streams as $stream) {
            foreach ($stream->getRequirements() as $requirement) {
                if ($requirement->getRulT() == "CC") {
                    $min = $requirement->getMin();
                    $remaining_defns = array_merge($remaining_defns, $requirement->getRawDefn());

                    foreach ($this->courses as $course) {
                        foreach ($requirement->getRawDefn() as $defn) {
                            if (strpos($defn->getCode(), $course->getCode()) !== false && $course->getOutcome() != 2) {
                                $remaining_defns = removeArrayElements($remaining_defns, $defn);
                                $min -= $course->getUOC();
                            }
                        }
                    }

                    for ($j = 0; $j < $i; $j++) {
                        $remaining_defns = removeArrayElements($remaining_defns, $remaining_requirements[$j]->getRawDefn());
                    }

                    if (!empty($remaining_defns)) {
                        $remaining_requirement = new Requirement($requirement->getRecT(), $requirement->getRulT(), $requirement->getTitle(), $requirement->getAppl(), $min, $requirement->getMax(), $remaining_defns);
                        $remaining_requirements[$i++] = $remaining_requirement;
                    }
                }
            }
        }

        return $remaining_requirements;
    }

    // Get the remaining PE (Professional Electives) requirements of the program or stream taken
    // @return $remaining_requirements - array of remaining PE Requirement objects
    public function getRemainingPERequirements() {
        include("inc/pgsql.php");
        $i = 0;
        $cc_requirements = array();
        $remaining_requirements = array();
        $remaining_defns = array();

        foreach ($this->streams as $stream) {
            foreach ($stream->getRequirements() as $requirement) {
                if ($requirement->getRulT() == "CC") {
                    foreach ($requirement->getRawDefn() as $defn) {
                        $key = $defn->getCode();
                        $cc_requirements[$key] = $requirement;
                    }
                }
            }
        }

        foreach ($this->streams as $stream) {
            foreach ($stream->getRequirements() as $requirement) {
                if ($requirement->getRulT() == "PE") {
                    $min = $requirement->getMin();
                    $remaining_defns = array_merge($remaining_defns, $requirement->getRawDefn());

                    foreach ($this->courses as $course) {
                        foreach ($requirement->getRawDefn() as $defn) {
                            if (array_key_exists($defn->getCode(), $cc_requirements) && strpos($defn->getCode(), $course->getCode()) !== false && $course->getOutcome() != 2) {
                                $remaining_defns = removeArrayElements($remaining_defns, $defn);
                                $min -= $course->getUOC();
                            }
                        }
                    }

                    for ($j = 0; $j < $i; $j++) {
                        $remaining_defns = removeArrayElements($remaining_defns, $remaining_requirements[$j]->getRawDefn());
                    }

                    if (!empty($remaining_defns)) {
                        $remaining_requirement = new Requirement($requirement->getRecT(), $requirement->getRulT(), $requirement->getTitle(), $requirement->getAppl(), $min, $requirement->getMax(), $remaining_defns);
                        $remaining_requirements[$i++] = $remaining_requirement;
                    }
                }
            }
        }

        return $remaining_requirements;
    }

    // Recommend courses based on popularity among other students with similar subjects
    // @param $courses - array of available Course objects
    // @return $recommendation - array of 100 recommended course
    function recommendPopularCourses($courses) {
        include("inc/pgsql.php");
        $recommendations = array();

        $query = "SELECT code, title
                  FROM (SELECT p.code, p.title, sum(p.counter)
                        FROM (SELECT *
                              FROM (SELECT fd.student_id, fd.code, fd.title, count(*) AS counter
                                    FROM course_enrolments ce
                                    JOIN full_details fd ON fd.student_id = ce.student_id
                                    WHERE ce.course_id IN (SELECT course_id
                                                           FROM course_enrolments
                                                           WHERE student_id = {$this->getZID()} AND (grade = 'PC' OR grade = 'PS' OR grade = 'CR' OR grade = 'DN' OR grade = 'HD' OR grade = 'SY'))
                                    GROUP BY fd.student_id, fd.title, fd.code
                                    ORDER BY counter DESC) AS q
                              WHERE q.counter > 4) AS p
                        WHERE p.title NOT IN (SELECT title
                                              FROM full_details
                                              WHERE student_id = {$this->getZID()})
                                              GROUP BY p.title, p.code
                                              ORDER BY sum DESC) AS r";
        $result = pg_query($sims_db_connection, $query);
        $total = 0;
        while ($rows = pg_fetch_array($result)) {
            if ($this->checkEligibility($courses, $rows["code"]) && $total < 100) {
                $recommendations[$total++] = $courses[$rows["code"] . $this->getProgram()->getCareer()];
            }
        }

        return $recommendations;
    }

    // Check whether the given course can be taken based on all its requirement types
    // i.e. prerequisites, corequisites, equivalence requirements, exclusion requirements
    // @param $courses - array of available Course objects
    // @param $course_to_check - course code to check ($code)
    // @return 1 if eligible
    //         0 if ineligible
    //         -1 if error
    public function checkEligibility($courses, $course_to_check) {
        if (!array_key_exists($course_to_check . $this->getProgram()->getCareer(), $courses)) {
            return;
        }

        $test_outcome = -1;
        $test_has_done_course = $this->hasDoneCourse($course_to_check);
        $test_check_prereq = $this->checkPrereq($courses, $course_to_check);
        $test_check_coreq = $this->checkCoreq($courses, $course_to_check);
        $test_check_equiv = $this->checkEquiv($courses, $course_to_check);
        $test_check_excl = $this->checkExcl($courses, $course_to_check);
        // echo "{$test_has_done_course} - {$test_check_prereq} - {$test_check_coreq} -  {$test_check_equiv} - {$test_check_excl}";

        if ($test_has_done_course != -1) {
            if ($test_has_done_course == 1) {
                $test_has_done_course = 1;
            } else {
                $test_has_done_course = 0;
            }
        }

        if ($test_check_prereq != -1) {
            if ($test_check_prereq == 1) {
                $test_check_prereq = 1;
            } else {
                $test_check_prereq = 0;
            }
        }

        if ($test_check_coreq != -1) {
            if ($test_check_coreq == 1) {
                $test_check_coreq = 1;
            } else {
                $test_check_coreq = 0;
            }
        }

        if ($test_check_equiv != -1) {
            if ($test_check_equiv == 1) {
                $test_check_equiv = 1;
            } else {
                $test_check_equiv = 0;
            }
        }

        if ($test_check_excl != -1) {
            if ($test_check_excl == 1) {
                $test_check_excl = 1;
            } else {
                $test_check_excl = 0;
            }
        }

        $test_outcome = eval("return ((~({$test_has_done_course}))&{$test_check_prereq}&{$test_check_coreq}&(~({$test_check_equiv}))&(~({$test_check_excl})));");

        return $test_outcome;
    }

    // Check whether the given course can be taken based on its prerequisites
    // @param $courses - array of available Course objects
    // @param $course_to_check - course code to check ($code)
    // @return 1 if eligible
    //         0 if ineligible
    //         -1 if error
    // TODO Degree-type checking (MARKETING_HONOURS etc.)
    public function checkPrereq($courses, $course_to_check) {
        $prereq_evaluation = array();
        $career = $this->getProgram()->getCareer();
        $courses_passed = $this->getPassedCourses();
        $key = $course_to_check . $this->getProgram()->getCareer();
        $prereq_conditions = explode(" ", $courses[$key]->getPrereq());

        // Check if the course to check has no prerequisite
        if (count($prereq_conditions) <= 1) {
            return 1;
        }

        for ($i = 0; $i < count($prereq_conditions); $i++) {
            // Check individual prerequisite course
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $prereq_conditions[$i])) {
                $prereq_evaluation[$i] = "FALSE";
                    if (array_key_exists($prereq_conditions[$i], $courses_passed)) {
                        $prereq_evaluation[$i] = "TRUE";
                    } else {
                        if (array_key_exists($prereq_conditions[$i] . $career, $courses)) {
                            foreach ($courses_passed as $course_passed) {
                                if (strpos($courses[$prereq_conditions[$i] . $career]->getEquiv(), $course_passed->getCode()) != false) {
                                    $prereq_evaluation[$i] = "TRUE";
                                }
                            }
                        }
                    }
            // Check individual prerequisite course with a minimum grade
            } else if (preg_match("/^([A-Z]{4}[0-9]{4})\{([A-Z0-9]{2})\}$/", $prereq_conditions[$i], $matches)) {
                $prereq_evaluation[$i] = "FALSE";

                if (array_key_exists($matches[1], $courses_passed)) {
                    if (preg_match("/^([0-9]{2})$/", $matches[2])) {
                        if ($courses_passed[$matches[1]]->getMark() >= $matches[2]) {
                            $prereq_evaluation[$i] = "TRUE";
                        }
                    } else {
                        if (strcmp($matches[2], "PS") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "PS") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $prereq_evaluation[$i] = "TRUE";
                            }
                        } else if (strcmp($matches[2], "CR") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $prereq_evaluation[$i] = "TRUE";
                            }
                        } else if (strcmp($matches[2], "DN") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $prereq_evaluation[$i] = "TRUE";
                            }
                        } else if (strcmp($matches[2], "HD") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $prereq_evaluation[$i] = "TRUE";
                            }
                        }
                    }
                }
            // Check individual prerequisite course with minimum UOC requirement
            } else if (preg_match("/^([0-9]{1,3})_UOC$/", $prereq_conditions[$i], $matches)) {
                if ($this->getUOC() >= $matches[1]) {
                    $prereq_evaluation[$i] = "TRUE";
                } else {
                    $prereq_evaluation[$i] = "FALSE";
                }
            // Check individual prerequisite course with minimum UNSW WAM requirement
            } else if (preg_match("/^([0-9]{1,3})_WAM$/", $prereq_conditions[$i], $matches)) {
                if ($this->getWAM() >= $matches[1]) {
                    $prereq_evaluation[$i] = "TRUE";
                } else {
                    $prereq_evaluation[$i] = "FALSE";
                }
            // Check individual prerequisite course with program enrolment requirement
            } else if (preg_match("/^([0-9]{4})$/", $prereq_conditions[$i], $matches)) {
                if ($this->getProgram()->getCode() == $matches[1]) {
                    $prereq_evaluation[$i] = "TRUE";
                } else {
                    $prereq_evaluation[$i] = "FALSE";
                }
            // Check individual prerequisite course with UOC subject checking (i.e. 12_UOC_LEVEL_1_CHEM, 6_UOC_LEVEL_1_BABS_BIOS)
            } else if (preg_match("/^([0-9]{1,3})_UOC_LEVEL_([0-9])(_([A-Z]{4}))(_([A-Z]{4}))?$/", $prereq_conditions[$i], $matches)) {
                $regex = "/^(";
                $uoc_required = $matches[1];
                for ($j = 4; $j < count($matches); $j += 2) {
                    if ($j > 4) {
                        $regex .= "|";
                    }
                    $regex .= $matches[$j];
                    $regex .= $matches[2];
                    $regex .= "...";
                    $j += 2;
                }
                $regex .= ")$/";
                $prereq_evaluation[$i] = $this->calculateUOCCourses($uoc_required, $regex);
            // Check individual prerequisite course with school approval requirement
            } else if (strcmp($prereq_conditions[$i], "SCHOOL_APPROVAL") == 0) {
                $prereq_evaluation[$i] = "FALSE";
            // Other cases that are not handled yet
            } else if (preg_match("/^[A-Z_a-z0-9]+$/", $prereq_conditions[$i])) {
                $prereq_evaluation[$i] = "FALSE";
            // Include brackets and other operators
            } else {
                $prereq_evaluation[$i] = $prereq_conditions[$i];
            }
        }

        $prereq_evaluation_string = implode(" ", $prereq_evaluation);
        $prereq_evaluation_string = str_ireplace("||", "|", $prereq_evaluation_string);
        $prereq_evaluation_string = str_ireplace("&&", "&", $prereq_evaluation_string);
        // echo $prereq_evaluation_string;
        if (preg_match("/(TRUE|FALSE)\s*\(/i", $prereq_evaluation_string)) {
            return -1;
        }

        return eval("return {$prereq_evaluation_string};");
    }

    // Check whether the given course can be taken based on its corequisites
    // @param $courses - array of available Course objects
    // @param $course_to_check - course code to check ($code)
    // @return 1 if eligible
    //         0 if ineligible
    //         -1 if error
    // TODO Degree-type checking (MARKETING_HONOURS etc.)
    public function checkCoreq($courses, $course_to_check) {
        $coreq_evaluation = array();
        $career = $this->getProgram()->getCareer();
        $courses_passed = $this->getPassedCourses();
        $key = $course_to_check . $this->getProgram()->getCareer();
        $coreq_conditions = explode(" ", $courses[$key]->getCoreq());

        // Check if the course to check has no corequisite
        if (count($coreq_conditions) <= 1) {
            return 1;
        }

        for ($i = 0; $i < count($coreq_conditions); $i++) {
            // Check individual corequisite course
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $coreq_conditions[$i])) {
                $coreq_evaluation[$i] = "FALSE";
                    if (array_key_exists($coreq_conditions[$i], $courses_passed)) {
                        $coreq_evaluation[$i] = "TRUE";
                    } else {
                        if (array_key_exists($coreq_conditions[$i] . $career, $courses)) {
                            foreach ($courses_passed as $course_passed) {
                                if (strpos($courses[$coreq_conditions[$i] . $career]->getEquiv(), $course_passed->getCode()) != false) {
                                    $coreq_evaluation[$i] = "TRUE";
                                }
                            }
                        }
                    }
            // Check individual corequisite course with a minimum grade
            } else if (preg_match("/^([A-Z]{4}[0-9]{4})\{([A-Z0-9]{2})\}$/", $coreq_conditions[$i], $matches)) {
                $coreq_evaluation[$i] = "FALSE";

                if (array_key_exists($matches[1], $courses_passed)) {
                    if (preg_match("/^([0-9]{2})$/", $matches[2])) {
                        if ($courses_passed[$matches[1]]->getMark() >= $matches[2]) {
                            $coreq_evaluation[$i] = "TRUE";
                        }
                    } else {
                        if (strcmp($matches[2], "PS") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "PS") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $coreq_evaluation[$i] = "TRUE";
                            }
                        } else if (strcmp($matches[2], "CR") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $coreq_evaluation[$i] = "TRUE";
                            }
                        } else if (strcmp($matches[2], "DN") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $coreq_evaluation[$i] = "TRUE";
                            }
                        } else if (strcmp($matches[2], "HD") == 0) {
                            if (strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                $coreq_evaluation[$i] = "TRUE";
                            }
                        }
                    }
                }
            // Check individual corequisite course with minimum UOC requirement
            } else if (preg_match("/^([0-9]{1,3})_UOC$/", $coreq_conditions[$i], $matches)) {
                if ($this->getUOC() >= $matches[1]) {
                    $coreq_evaluation[$i] = "TRUE";
                } else {
                    $coreq_evaluation[$i] = "FALSE";
                }
            // Check individual corequisite course with minimum UNSW WAM requirement
            } else if (preg_match("/^([0-9]{1,3})_WAM$/", $coreq_conditions[$i], $matches)) {
                if ($this->getWAM() >= $matches[1]) {
                    $coreq_evaluation[$i] = "TRUE";
                } else {
                    $coreq_evaluation[$i] = "FALSE";
                }
            // Check individual corequisite course with program enrolment requirement
            } else if (preg_match("/^([0-9]{4})$/", $coreq_conditions[$i], $matches)) {
                if ($this->getProgram()->getCode() == $matches[1]) {
                    $coreq_evaluation[$i] = "TRUE";
                } else {
                    $coreq_evaluation[$i] = "FALSE";
                }
            // Check individual corequisite course with UOC subject checking (i.e. 12_UOC_LEVEL_1_CHEM, 6_UOC_LEVEL_1_BABS_BIOS)
            } else if (preg_match("/^([0-9]{1,3})_UOC_LEVEL_([0-9])(_([A-Z]{4}))(_([A-Z]{4}))?$/", $coreq_conditions[$i], $matches)) {
                $regex = "/^(";
                $uoc_required = $matches[1];
                for ($j = 4; $j < count($matches); $j += 2) {
                    if ($j > 4) {
                        $regex .= "|";
                    }
                    $regex .= $matches[$j];
                    $regex .= $matches[2];
                    $regex .= "...";
                    $j += 2;
                }
                $regex .= ")$/";
                $coreq_evaluation[$i] = $this->calculateUOCCourses($uoc_required, $regex);
            // Check individual corequisite course with school approval requirement
            } else if (strcmp($coreq_conditions[$i], "SCHOOL_APPROVAL") == 0) {
                $coreq_evaluation[$i] = "FALSE";
            // Other cases that are not handled yet
            } else if (preg_match("/^[A-Z_a-z0-9]+$/", $coreq_conditions[$i])) {
                $coreq_evaluation[$i] = "FALSE";
            // Include brackets and other operators
            } else {
                $coreq_evaluation[$i] = $coreq_conditions[$i];
            }
        }

        $coreq_evaluation_string = implode(" ", $coreq_evaluation);
        $coreq_evaluation_string = str_ireplace("||", "|", $coreq_evaluation_string);
        $coreq_evaluation_string = str_ireplace("&&", "&", $coreq_evaluation_string);
        // echo $coreq_evaluation_string;
        if (preg_match("/(TRUE|FALSE)\s*\(/i", $coreq_evaluation_string)) {
            return -1;
        }

        return eval("return {$coreq_evaluation_string};");
    }

    // Check whether the given course can be taken based on its equivalence requirements
    // @param $courses - array of available Course objects
    // @param $course_to_check - course code to check ($code)
    // @return 1 if eligible
    //         0 if ineligible
    //         -1 if error
    public function checkEquiv($courses, $course_to_check) {
        $equiv_evaluation = array();
        $courses_passed = $this->getPassedCourses();
        $key = $course_to_check . $this->getProgram()->getCareer();
        $equiv_conditions = explode(" ", $courses[$key]->getEquiv());

        // Check if the course to check has no equivalence
        if (count($equiv_conditions) <= 1) {
            return 0;
        }

        for ($i = 0; $i < count($equiv_conditions); $i++) {
            // Check individual equivalence course
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $equiv_conditions[$i])) {
                $equiv_evaluation[$i] = "FALSE";
                    if (array_key_exists($equiv_conditions[$i], $courses_passed)) {
                        $equiv_evaluation[$i] = "TRUE";
                    }
            // Other cases that are not handled yet
            } else if (preg_match("/^[A-Z_a-z0-9]+$/", $equiv_conditions[$i])) {
                $equiv_evaluation[$i] = "FALSE";
            // Include brackets and other operators
            } else {
                $equiv_evaluation[$i] = $equiv_conditions[$i];
            }
        }

        $equiv_evaluation_string = implode(" ", $equiv_evaluation);
        $equiv_evaluation_string = str_ireplace("||", "|", $equiv_evaluation_string);
        $equiv_evaluation_string = str_ireplace("&&", "&", $equiv_evaluation_string);
        if (preg_match("/(TRUE|FALSE)\s*\(/i", $equiv_evaluation_string)) {
            return -1;
        }

        return eval("return {$equiv_evaluation_string};");
    }

    // Check whether the given course can be taken based on its exclusion requirements
    // @param $courses - array of available Course objects
    // @param $course_to_check - course code to check ($code)
    // @return 1 if eligible
    //         0 if ineligible
    //         -1 if error
    public function checkExcl($courses, $course_to_check) {
        $excl_evaluation = array();
        $courses_passed = $this->getPassedCourses();
        $key = $course_to_check . $this->getProgram()->getCareer();
        $excl_conditions = explode(" ", $courses[$key]->getExcl());

        // Check if the course to check has no exclusion
        if (count($excl_conditions) <= 1) {
            return 0;
        }

        for ($i = 0; $i < count($excl_conditions); $i++) {
            // Check individual equivalence course
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $excl_conditions[$i])) {
                $excl_evaluation[$i] = "FALSE";
                    if (array_key_exists($excl_conditions[$i], $courses_passed)) {
                        $excl_evaluation[$i] = "TRUE";
                    }
            // Other cases that are not handled yet
            } else if (preg_match("/^[A-Z_a-z0-9]+$/", $excl_conditions[$i])) {
                $excl_evaluation[$i] = "FALSE";
            // Include brackets and other operators
            } else {
                $excl_evaluation[$i] = $excl_conditions[$i];
            }
        }

        $excl_evaluation_string = implode(" ", $excl_evaluation);
        $excl_evaluation_string = str_ireplace("||", "|", $excl_evaluation_string);
        $excl_evaluation_string = str_ireplace("&&", "&", $excl_evaluation_string);
        if (preg_match("/(TRUE|FALSE)\s*\(/i", $excl_evaluation_string)) {
            return -1;
        }

        return eval("return {$excl_evaluation_string};");
    }

    // Check whether the user meets the minimum UOC for the course
    public function calculateUOCCourses($uoc_required, $pattern) {
        $uoc_acquired = 0;
        $keys = array_keys($this->getPassedCourses());
        $courses_passed = $this->getPassedCourses();

        foreach ($keys as $key) {
            if (preg_match($pattern, $key)) {
                $uoc_acquired += $courses_passed[$key]->getUOC();
            }
        }

        if ($uoc_acquired >= $uoc_required) {
            return "TRUE";
        } else {
            return "FALSE";
        }
    }

    // Get the remaining UOC to complete the program
    // @return $remaining_uoc - remaning UOC
    public function getRemainingUOC() {
        $remaining_uoc = $this->getProgram()->getUOC() - $this->uoc;

        return $remaining_uoc;
    }

    // Check whether the user has taken the given course previously
    // @param $course_to_check - course code to check ($code)
    // @return 1 if taken
    //         0 if not taken
    public function hasDoneCourse($course_to_check) {
        if (preg_match("/^([A-Z]{4}[0-9]{4})/", $course_to_check, $matches)) {
            $courses_passed = $this->getPassedCourses();
            if (array_key_exists($matches[1], $courses_passed)) {
                return 1;
            }
        }

        return 0;
    }

    // Get the courses passed by the user
    // @return $courses_passed - array of passed Course objects
    public function getPassedCourses() {
        $courses_passed = array();

        foreach ($this->courses as $course) {
            if ($course->getOutcome() == 0 || $course->getOutcome() == 1) {   // Active or passed course
                $key = $course->getCode();
                $courses_passed[$key] = $course;
            }
        }

        return $courses_passed;
    }

    public function getZID() {
        return $this->zid;
    }

    public function getGivenName() {
        return $this->given_name;
    }

    public function getFamilyName() {
        return $this->family_name;
    }

    public function getUOC() {
        return $this->uoc;
    }

    public function getWAM() {
        return $this->wam;
    }

    public function getProgram() {
        return $this->program;
    }

    public function getStreams() {
        return $this->streams;
    }

    public function getCourses() {
        return $this->courses;
    }

    // Get the requirements of a program or stream
    // @param $code - program or stream code
    // @param $career - career of the course
    // @param $all_courses - array of all available Course objects
    // @return $requirements - array of Requirement objects
    private function getRequirements($code, $career, $all_courses) {
        include("inc/pgsql.php");
        $i = 0;
        $requirements = array();
        $course_list = array();

        $query = "SELECT rec_t, rul_t, title, appl, min, max, raw_defn
                  FROM active_rules
                  WHERE LOWER(code) LIKE LOWER('$code')
                  ORDER BY title";
        $result = pg_query($aims_db_connection, $query);
        while ($rows = pg_fetch_array($result)) {
            $j = 0;
            $raw_defn = array();
            $rec_t = $rows["rec_t"];
            $rul_t = $rows["rul_t"];
            $title = $rows["title"];
            $appl = $rows["appl"];
            $min = $rows["min"];
            $max = $rows["max"];
            if ($max == null) {
                $max = $min;
            }
            $defn_list = explode(",", toPHPRawDefn($rows["raw_defn"]));
            foreach ($defn_list as $defn) {
                $key = $defn . $career;

                if (array_key_exists($key, $all_courses)) {
                    $course_code = $all_courses[$key]->getCode();
                    $course_title = $all_courses[$key]->getTitle();
                    $course_career = $all_courses[$key]->getCareer();
                    $course_uoc = $all_courses[$key]->getUOC();
                    $course_prereq = $all_courses[$key]->getPrereq();
                    $course_coreq = $all_courses[$key]->getCoreq();
                    $course_equiv = $all_courses[$key]->getEquiv();
                    $course_excl = $all_courses[$key]->getExcl();
                    $raw_defn[$j++] = new Course($course_code, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl);
                } else if (strpos($defn, "|") !== false) {
                    $course_codes = str_ireplace("(", "", $defn);
                    $course_codes = str_ireplace(")", "", $course_codes);
                    $course_code = explode("|", $course_codes)[0];
                    $key = $course_code . $career;
                    $course_title = $all_courses[$key]->getTitle();
                    $course_career = $all_courses[$key]->getCareer();
                    $course_uoc = $all_courses[$key]->getUOC();
                    $course_prereq = $all_courses[$key]->getPrereq();
                    $course_coreq = $all_courses[$key]->getCoreq();
                    $course_equiv = $all_courses[$key]->getEquiv();
                    $course_excl = $all_courses[$key]->getExcl();
                    $raw_defn[$j++] = new Course($defn, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl);
                } else if (strpos($defn, ".") !== false && strlen($code) != 4) {
                    foreach ($all_courses as $course) {
                        $key = $course->getCode() . $career;

                        if (preg_match("/$defn/", $course->getCode()) && array_key_exists($key, $all_courses)) {
                            $course_code = $all_courses[$key]->getCode();
                            $course_title = $all_courses[$key]->getTitle();
                            $course_career = $all_courses[$key]->getCareer();
                            $course_uoc = $all_courses[$key]->getUOC();
                            $course_prereq = $all_courses[$key]->getPrereq();
                            $course_coreq = $all_courses[$key]->getCoreq();
                            $course_equiv = $all_courses[$key]->getEquiv();
                            $course_excl = $all_courses[$key]->getExcl();
                            $raw_defn[$j++] = new Course($course_code, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl);
                        }
                    }
                } else {
                    $raw_defn[$j++] = new Course($defn, null, $career, null, null, null, null, null);
                }
            }

            $requirement = new Requirement($rec_t, $rul_t, $title, $appl, $min, $max, $raw_defn);
            $requirements[$i++] = $requirement;
        }

        return $requirements;
    }

    // Get the info of the program or course taken
    // @param $zid - zID
    // @param $type - either "program" or "stream"
    // @return $result - DB result (require pg_fetch_array)
    private function getTypeInfo($zid, $type) {
        include("inc/pgsql.php");
        $result = NULL;

        $min_counter = $this->getMinCounter($zid, $type);
        $query = "SELECT * FROM
                     (SELECT t.id, t.code AS code, t.title AS title, t.career AS career, t.uoc AS uoc, MAX(te.term_id), COUNT(t.id) AS counter
                     FROM people p, {$type}_enrolments te, {$type}s t
                     WHERE p.id = $zid AND p.id = te.student_id AND te.{$type}_id = t.id
                     GROUP BY t.id ORDER BY t.id) AS q
                  WHERE q.counter = $min_counter";
        $result = pg_query($sims_db_connection, $query);

        return $result;
    }

    // Get the info of the course taken
    // @param $zid - zID
    // @return $result - DB result (require pg_fetch_array)
    private function getCourseInfo($zid) {
        include("inc/pgsql.php");
        $result = NULL;

        $query = "SELECT tr.code AS code, tr.title AS title, tr.mark AS mark, tr.grade AS grade, tr.career AS career, tr.uoc AS uoc, tr.term AS term, t.id
                  FROM transcript tr, terms t
                  WHERE tr.student_id = $zid AND tr.term LIKE t.code
                  GROUP BY tr.code, tr.title, tr.mark, tr.grade, tr.career, tr.uoc, tr.term, t.id
                  ORDER BY t.id, tr.code";
        $result = pg_query($sims_db_connection, $query);

        return $result;
    }

    // Get the minimum counter (fix SIMS bugs)
    // @param $zid - zID
    // @param $type - either "program" or "stream"
    // @return $min_counter - minimum counter
    private function getMinCounter($zid, $type) {
        include("inc/pgsql.php");
        $min_counter = 0;

        $query = "SELECT MIN(q.counter) AS min_counter FROM (SELECT COUNT(t.id) AS counter
                  FROM people p, {$type}_enrolments te, {$type}s t
                  WHERE p.id = $zid AND p.id = te.student_id AND te.{$type}_id = t.id
                  GROUP BY t.id ORDER BY t.id) AS q";
        $result = pg_query($sims_db_connection, $query);
        $rows = pg_fetch_array($result);
        $min_counter = $rows["min_counter"];

        return $min_counter;
    }
}

?>
