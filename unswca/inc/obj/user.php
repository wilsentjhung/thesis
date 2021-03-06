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
        include_once("program_taken.php");
        include_once("stream_taken.php");
        include_once("course_taken.php");
        include_once("requirement.php");

        $query = "SELECT * FROM people WHERE id = $zid";
        $result = pg_query($sims_db_connection, $query);
        $rows = pg_fetch_array($result);
        $zid = str_ireplace(" ", "", $rows["id"]);
        $given_name = $rows["given_name"];
        $family_name = $rows["family_name"];

        $this->all_courses = $all_courses;
        $this->zid = $zid;
        $this->given_name = $given_name;
        $this->family_name = $family_name;

        // Construct ProgramTaken object
        $result = $this->getProgramOrStreamInfo("program");
        $rows = pg_fetch_array($result);
        $code = str_ireplace(" ", "", $rows["code"]);
        $title = $rows["title"];
        $career = str_ireplace(" ", "", $rows["career"]);
        $pr_uoc = str_ireplace(" ", "", $rows["uoc"]);
        // Check the school and faculty responsible for the program
        $school_faculty = pg_fetch_array(getSchoolAndFaculty($code));
        $school = $school_faculty["school"];
        $faculty = $school_faculty["faculty"];
        // Get the program requirements
        $requirements = $this->getRequirements($code, $career);
        $is_dual_award = isDualAward($code);

        $program = new ProgramTaken($code, $title, $career, $pr_uoc, $school, $faculty, $requirements, $is_dual_award);

        // Construct StreamTaken object
        $i = 0;
        $streams = array();
        $result = $this->getProgramOrStreamInfo("stream");
        while ($rows = pg_fetch_array($result)) {
            $code = str_ireplace(" ", "", $rows["code"]);
            $title = $rows["title"];
            $career = str_ireplace(" ", "", $rows["career"]);
            $st_uoc = str_ireplace(" ", "", $rows["uoc"]);
            // Check the school and faculty responsible for the stream
            $school_faculty = pg_fetch_array(getSchoolAndFaculty($code));
            $school = $school_faculty["school"];
            $faculty = $school_faculty["faculty"];

            $requirements = $this->getRequirements($code, $program->getCareer());

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
        $result = $this->getCourseInfo();
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
            if ($course->getGrade() == "SY" || $course->getGrade() == "RS") {          // i.e. COMP4930 (Thesis Part A)
                $uoc += $course->getUOC();
            } else if ($course->getOutcome() == 1) {    // Passed course
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
                $uoc += $course->getUOC();
            } else if ($course->getOutcome() == 2) {    // Failed course
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
            } else if ($course->getOutcome() == 4) {    // Other course i.e. exchange, research course
                $uoc += $course->getUOC();
            }
        }

        if ($uoc == 0 || $denominator == 0) {
            $wam = 0;
        } else {
            $wam = $numerator/$denominator;
            $wam = round($wam, 3);
        }

        $this->uoc = $uoc;
        $this->wam = $wam;
        $this->program = $program;
        $this->streams = $streams;
        $this->courses = $courses;
    }

    // Get the remaining CC (Core Courses) requirements of the program or stream taken.
    // @return $remaining_requirements - remaining CC requirements (Requirement[])
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

    // Get the remaining PE (Professional Electives) requirements of the program or stream taken.
    // @return $remaining_requirements - remaining PE requirements (Requirement[])
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

    // Get the remaining UOC to complete the program.
    // @return $remaining_uoc - remaning UOC (int)
    public function getRemainingUOC() {
        $remaining_uoc = $this->getProgram()->getUOC() - $this->uoc;

        return $remaining_uoc;
    }

    // Get the courses passed by the user.
    // @return $courses_passed - courses passed (Course[])
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

    public function getAllCourses() {
        return $this->all_courses;
    }

    // Get the requirements of a program or stream.
    // @param $code - program or stream code (String)
    // @param $career - program or stream career (String)
    // @return $requirements - program or stream requirements (Requirement[])
    private function getRequirements($code, $career) {
        include("inc/pgsql.php");
        $i = 0;
        $requirements = array();
        $course_list = array();

        if (strlen($code) == 4) {           // Program
            $program_appl = getProgramApplicability($code);
            $programs = findSingleAwardPrograms($code);

            foreach ($programs as $program) {
                $query = "SELECT rec_t, rul_t, title, appl, min, max, raw_defn
                          FROM all_rules
                          WHERE LOWER(code) LIKE LOWER('$program')
                          ORDER BY title";
                $result = pg_query($aims_db_connection, $query);
                while ($rows = pg_fetch_array($result)) {
                    $rec_t = $rows["rec_t"];
                    $rul_t = $rows["rul_t"];
                    $title = $rows["title"];
                    $appl = $rows["appl"];
                    $min = $rows["min"];
                    $max = $rows["max"];
                    if ($max == null) {
                        $max = $min;
                    }

                    if ($appl == $program_appl || $appl == "A") {
                        $j = 0;
                        $raw_defn = array();
                        $defn_list = explode(",", toPHPRawDefn($rows["raw_defn"]));

                        foreach ($defn_list as $defn) {
                            $key = $defn . $career;

                            if (array_key_exists($key, $this->all_courses)) {
                                $course_code = $this->all_courses[$key]->getCode();
                                $course_title = $this->all_courses[$key]->getTitle();
                                $course_career = $this->all_courses[$key]->getCareer();
                                $course_uoc = $this->all_courses[$key]->getUOC();
                                $course_prereq = $this->all_courses[$key]->getPrereq();
                                $course_coreq = $this->all_courses[$key]->getCoreq();
                                $course_equiv = $this->all_courses[$key]->getEquiv();
                                $course_excl = $this->all_courses[$key]->getExcl();
                                $raw_defn[$j++] = new Course($course_code, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl);
                            } else if (strpos($defn, "|") !== false) {
                                $course_codes = str_ireplace("(", "", $defn);
                                $course_codes = str_ireplace(")", "", $course_codes);
                                $course_code = explode("|", $course_codes)[0];
                                $key = $course_code . $career;
                                $course_title = $this->all_courses[$key]->getTitle();
                                $course_career = $this->all_courses[$key]->getCareer();
                                $course_uoc = $this->all_courses[$key]->getUOC();
                                $course_prereq = $this->all_courses[$key]->getPrereq();
                                $course_coreq = $this->all_courses[$key]->getCoreq();
                                $course_equiv = $this->all_courses[$key]->getEquiv();
                                $course_excl = $this->all_courses[$key]->getExcl();
                                $raw_defn[$j++] = new Course($defn, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl);
                            } else {
                                $raw_defn[$j++] = new Course($defn, null, $career, null, null, null, null, null, null);
                            }
                        }

                        $requirement = new Requirement($rec_t, $rul_t, $title, $appl, $min, $max, $raw_defn);
                        $requirements[$i++] = $requirement;
                    }
                }
            }
        } else if (strlen($code) == 6) {    // Stream
            $query = "SELECT rec_t, rul_t, title, appl, min, max, raw_defn
                      FROM all_rules
                      WHERE LOWER(code) LIKE LOWER('$code')
                      ORDER BY title";
            $result = pg_query($aims_db_connection, $query);
            while ($rows = pg_fetch_array($result)) {
                $rec_t = $rows["rec_t"];
                $rul_t = $rows["rul_t"];
                $title = $rows["title"];
                $appl = $rows["appl"];
                $min = $rows["min"];
                $max = $rows["max"];
                if ($max == null) {
                    $max = $min;
                }

                $j = 0;
                $raw_defn = array();
                $defn_list = explode(",", toPHPRawDefn($rows["raw_defn"]));

                foreach ($defn_list as $defn) {
                    $key = $defn . $career;

                    if (array_key_exists($key, $this->all_courses)) {
                        $course_code = $this->all_courses[$key]->getCode();
                        $course_title = $this->all_courses[$key]->getTitle();
                        $course_career = $this->all_courses[$key]->getCareer();
                        $course_uoc = $this->all_courses[$key]->getUOC();
                        $course_prereq = $this->all_courses[$key]->getPrereq();
                        $course_coreq = $this->all_courses[$key]->getCoreq();
                        $course_equiv = $this->all_courses[$key]->getEquiv();
                        $course_excl = $this->all_courses[$key]->getExcl();
                        $subject_area = $this->all_courses[$key]->getSubjectArea();
                        $raw_defn[$j++] = new Course($course_code, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl, $subject_area);
                    } else if (strpos($defn, "|") !== false) {
                        $course_codes = str_ireplace("(", "", $defn);
                        $course_codes = str_ireplace(")", "", $course_codes);
                        $course_code = explode("|", $course_codes)[0];
                        $key = $course_code . $career;
                        $course_title = $this->all_courses[$key]->getTitle();
                        $course_career = $this->all_courses[$key]->getCareer();
                        $course_uoc = $this->all_courses[$key]->getUOC();
                        $course_prereq = $this->all_courses[$key]->getPrereq();
                        $course_coreq = $this->all_courses[$key]->getCoreq();
                        $course_equiv = $this->all_courses[$key]->getEquiv();
                        $course_excl = $this->all_courses[$key]->getExcl();
                        $subject_area = $this->all_courses[$key]->getSubjectArea();
                        $raw_defn[$j++] = new Course($defn, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl, $subject_area);
                    } else if (strpos($defn, ".") !== false && strlen($code) != 4) {
                        foreach ($this->all_courses as $course) {
                            $key = $course->getCode() . $career;

                            if (preg_match("/$defn/", $course->getCode()) && array_key_exists($key, $this->all_courses)) {
                                $course_code = $this->all_courses[$key]->getCode();
                                $course_title = $this->all_courses[$key]->getTitle();
                                $course_career = $this->all_courses[$key]->getCareer();
                                $course_uoc = $this->all_courses[$key]->getUOC();
                                $course_prereq = $this->all_courses[$key]->getPrereq();
                                $course_coreq = $this->all_courses[$key]->getCoreq();
                                $course_equiv = $this->all_courses[$key]->getEquiv();
                                $course_excl = $this->all_courses[$key]->getExcl();
                                $raw_defn[$j++] = new Course($course_code, $course_title, $course_career, $course_uoc, $course_prereq, $course_coreq, $course_equiv, $course_excl);
                            }
                        }
                    } else {
                        $raw_defn[$j++] = new Course($defn, null, $career, null, null, null, null, null, null);
                    }
                }

                $requirement = new Requirement($rec_t, $rul_t, $title, $appl, $min, $max, $raw_defn);
                $requirements[$i++] = $requirement;
            }
        }

        return $requirements;
    }

    // Get the info of the program or course taken.
    // @param $type - either "program" or "stream" (String)
    // @return $result - DB result (require pg_fetch_array)
    private function getProgramOrStreamInfo($type) {
        include("inc/pgsql.php");
        $result = NULL;

        $min_counter = $this->getMinCounter($type);
        $query = "SELECT * FROM
                     (SELECT t.id, t.code AS code, t.title AS title, t.career AS career, t.uoc AS uoc, MAX(te.term_id), COUNT(t.id) AS counter
                     FROM people p, {$type}_enrolments te, {$type}s t
                     WHERE p.id = $this->zid AND p.id = te.student_id AND te.{$type}_id = t.id
                     GROUP BY t.id ORDER BY t.id) AS q
                  WHERE q.counter = $min_counter";
        $result = pg_query($sims_db_connection, $query);

        return $result;
    }

    // Get the info of the course taken.
    // @return $result - DB result (require pg_fetch_array)
    private function getCourseInfo() {
        include("inc/pgsql.php");
        $result = NULL;

        $query = "SELECT tr.code AS code, tr.title AS title, tr.mark AS mark, tr.grade AS grade, tr.career AS career, tr.uoc AS uoc, tr.term AS term, t.id
                  FROM transcript tr, terms t
                  WHERE tr.student_id = $this->zid AND tr.term LIKE t.code
                  GROUP BY tr.code, tr.title, tr.mark, tr.grade, tr.career, tr.uoc, tr.term, t.id
                  ORDER BY t.id, tr.code";
        $result = pg_query($sims_db_connection, $query);

        return $result;
    }

    // Get the minimum counter (fix SIMS bugs)
    // @param $type - either "program" or "stream" (String)
    // @return $min_counter - minimum counter (int)
    private function getMinCounter($type) {
        include("inc/pgsql.php");
        $min_counter = 0;

        $query = "SELECT MIN(q.counter) AS min_counter FROM (SELECT COUNT(t.id) AS counter
                  FROM people p, {$type}_enrolments te, {$type}s t
                  WHERE p.id = $this->zid AND p.id = te.student_id AND te.{$type}_id = t.id
                  GROUP BY t.id ORDER BY t.id) AS q";
        $result = pg_query($sims_db_connection, $query);
        $rows = pg_fetch_array($result);
        $min_counter = $rows["min_counter"];

        return $min_counter;
    }
}

?>
