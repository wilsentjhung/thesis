<?php

// Recommend courses based on popularity among other students with similar subjects.
// @param $user - current user (User)
// @return $recommendations - recommended courses (String[])
function recommendPopularCourses($user) {
    include("inc/pgsql.php");
    global $courses;
    $recommendations = array();

    $query = "SELECT code, title
              FROM (SELECT p.code, p.title, sum(p.counter)
                    FROM (SELECT *
                          FROM (SELECT fd.student_id, fd.code, fd.title, count(*) AS counter
                                FROM course_enrolments ce
                                JOIN full_details fd ON fd.student_id = ce.student_id
                                WHERE ce.course_id IN (SELECT course_id
                                                       FROM course_enrolments
                                                       WHERE student_id = {$user->getZID()} AND (grade = 'PC' OR grade = 'PS' OR grade = 'CR' OR grade = 'DN' OR grade = 'HD' OR grade = 'SY'))
                                GROUP BY fd.student_id, fd.title, fd.code
                                ORDER BY counter DESC) AS q
                          WHERE q.counter > 4) AS p
                    WHERE p.title NOT IN (SELECT title
                                          FROM full_details
                                          WHERE student_id = {$user->getZID()})
                                          GROUP BY p.title, p.code
                                          ORDER BY sum DESC) AS r";
    $result = pg_query($sims_db_connection, $query);
    $total = 0;
    while ($rows = pg_fetch_array($result)) {
        if ($user->checkEligibility($courses, $rows["code"]) && $total < 100) {
            $recommendations[$total++] = $courses[$rows["code"] . $user->getProgram()->getCareer()];
        }
    }

    return $recommendations;
}

// Check whether the given course can be taken based on all its requirement types
// i.e. prerequisites, corequisites, equivalence requirements, exclusion requirements.
// @param $course_to_check - course code to check (String)
// @param $courses_passed - courses passed (Course[])
// @param $user - current user (User)
// @return 1 if eligible
//         0 if ineligible
//         -1 if error
function checkEligibility($course_to_check, $courses_passed, $user) {
    global $courses;

    if (!array_key_exists($course_to_check . $user->getProgram()->getCareer(), $courses)) {
        return;
    }

    $test_outcome = -1;
    $test_has_done_course = hasDoneCourse($course_to_check, $courses_passed, $user);
    $test_check_prereq = checkPrereq($course_to_check, $courses_passed, $user);
    $test_check_coreq = checkCoreq($course_to_check, $courses_passed, $user);
    $test_check_equiv = checkEquiv($course_to_check, $courses_passed, $user);
    $test_check_excl = checkExcl($course_to_check, $courses_passed, $user);
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

// Check whether the given course can be taken based on its prerequisites.
// @param $course_to_check - course code to check (String)
// @param $courses_passed - courses passed (Course[])
// @param $user - current user (User)
// @return 1 if eligible
//         0 if ineligible
//         -1 if error
// TODO Degree-type checking (MARKETING_HONOURS etc.)
function checkPrereq($course_to_check, $courses_passed, $user) {
    global $courses;
    $prereq_evaluation = array();
    $career = $user->getProgram()->getCareer();
    $key = $course_to_check . $user->getProgram()->getCareer();
    $prereq_conditions = explode(" ", $courses[$key]->getPrereq());

    // Check if the course to check has no prerequisite
    if (count($prereq_conditions) <= 1) {
        return 1;
    }

    for ($i = 0; $i < count($prereq_conditions); $i++) {
        // Check individual prerequisite course
        if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $prereq_conditions[$i])) {
            $prereq_evaluation[$i] = "false";

            if (array_key_exists($prereq_conditions[$i], $courses_passed)) {
                $prereq_evaluation[$i] = "true";
            } else {
                if (array_key_exists($prereq_conditions[$i] . $career, $courses)) {
                    foreach ($courses_passed as $course_passed) {
                        if (strpos($courses[$prereq_conditions[$i] . $career]->getEquiv(), $course_passed->getCode()) != false) {
                            $prereq_evaluation[$i] = "true";
                        }
                    }
                }
            }
        // Check individual prerequisite course with a minimum grade
        } else if (preg_match("/^([A-Z]{4}[0-9]{4})\{([A-Z0-9]{2})\}$/", $prereq_conditions[$i], $matches)) {
            $prereq_evaluation[$i] = "false";

            if (array_key_exists($matches[1], $courses_passed)) {
                if (preg_match("/^([0-9]{2})$/", $matches[2])) {
                    if ($courses_passed[$matches[1]]->getMark() >= $matches[2]) {
                        $prereq_evaluation[$i] = "true";
                    }
                } else {
                    if (strcmp($matches[2], "PS") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "PS") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $prereq_evaluation[$i] = "true";
                        }
                    } else if (strcmp($matches[2], "CR") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $prereq_evaluation[$i] = "true";
                        }
                    } else if (strcmp($matches[2], "DN") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $prereq_evaluation[$i] = "true";
                        }
                    } else if (strcmp($matches[2], "HD") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $prereq_evaluation[$i] = "true";
                        }
                    }
                }
            }
        // Check individual prerequisite course with minimum UOC requirement
        } else if (preg_match("/^([0-9]{1,3})_UOC$/", $prereq_conditions[$i], $matches)) {
            if ($user->getUOC() >= $matches[1]) {
                $prereq_evaluation[$i] = "true";
            } else {
                $prereq_evaluation[$i] = "false";
            }
        // Check individual prerequisite course with minimum UNSW WAM requirement
        } else if (preg_match("/^([0-9]{1,3})_WAM$/", $prereq_conditions[$i], $matches)) {
            if ($user->getWAM() >= $matches[1]) {
                $prereq_evaluation[$i] = "true";
            } else {
                $prereq_evaluation[$i] = "false";
            }
        // Check individual prerequisite course with program enrolment requirement
        } else if (preg_match("/^([0-9]{4})$/", $prereq_conditions[$i], $matches)) {
            if ($user->getProgram()->getCode() == $matches[1]) {
                $prereq_evaluation[$i] = "true";
            } else {
                $prereq_evaluation[$i] = "false";
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
            $prereq_evaluation[$i] = calculateUOCCourses($uoc_required, $regex, $courses_passed);
        // Check individual prerequisite course with school approval requirement
        } else if (strcmp($prereq_conditions[$i], "SCHOOL_APPROVAL") == 0) {
            $prereq_evaluation[$i] = "false";
        // Other cases that are not handled yet
        } else if (preg_match("/^[A-Z_a-z0-9]+$/", $prereq_conditions[$i])) {
            $prereq_evaluation[$i] = "false";
        // Include brackets and other operators
        } else {
            $prereq_evaluation[$i] = $prereq_conditions[$i];
        }
    }

    $prereq_evaluation_string = implode(" ", $prereq_evaluation);
    $prereq_evaluation_string = str_ireplace("||", "|", $prereq_evaluation_string);
    $prereq_evaluation_string = str_ireplace("&&", "&", $prereq_evaluation_string);
    // echo $prereq_evaluation_string;
    if (preg_match("/(true|false)\s*\(/i", $prereq_evaluation_string)) {
        return -1;
    }

    return eval("return {$prereq_evaluation_string};");
}

// Check whether the given course can be taken based on its corequisites.
// @param $course_to_check - course code to check (String)
// @param $courses_passed - courses passed (Course[])
// @param $user - current user (User)
// @return 1 if eligible
//         0 if ineligible
//         -1 if error
// TODO Degree-type checking (MARKETING_HONOURS etc.)
function checkCoreq($course_to_check, $courses_passed, $user) {
    global $courses;
    $coreq_evaluation = array();
    $career = $user->getProgram()->getCareer();
    $key = $course_to_check . $user->getProgram()->getCareer();
    $coreq_conditions = explode(" ", $courses[$key]->getCoreq());

    // Check if the course to check has no corequisite
    if (count($coreq_conditions) <= 1) {
        return 1;
    }

    for ($i = 0; $i < count($coreq_conditions); $i++) {
        // Check individual corequisite course
        if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $coreq_conditions[$i])) {
            $coreq_evaluation[$i] = "false";

            if (array_key_exists($coreq_conditions[$i], $courses_passed)) {
                $coreq_evaluation[$i] = "true";
            } else {
                if (array_key_exists($coreq_conditions[$i] . $career, $courses)) {
                    foreach ($courses_passed as $course_passed) {
                        if (strpos($courses[$coreq_conditions[$i] . $career]->getEquiv(), $course_passed->getCode()) != false) {
                            $coreq_evaluation[$i] = "true";
                        }
                    }
                }
            }
        // Check individual corequisite course with a minimum grade
        } else if (preg_match("/^([A-Z]{4}[0-9]{4})\{([A-Z0-9]{2})\}$/", $coreq_conditions[$i], $matches)) {
            $coreq_evaluation[$i] = "false";

            if (array_key_exists($matches[1], $courses_passed)) {
                if (preg_match("/^([0-9]{2})$/", $matches[2])) {
                    if ($courses_passed[$matches[1]]->getMark() >= $matches[2]) {
                        $coreq_evaluation[$i] = "true";
                    }
                } else {
                    if (strcmp($matches[2], "PS") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "PS") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $coreq_evaluation[$i] = "true";
                        }
                    } else if (strcmp($matches[2], "CR") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $coreq_evaluation[$i] = "true";
                        }
                    } else if (strcmp($matches[2], "DN") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $coreq_evaluation[$i] = "true";
                        }
                    } else if (strcmp($matches[2], "HD") == 0) {
                        if (strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                            $coreq_evaluation[$i] = "true";
                        }
                    }
                }
            }
        // Check individual corequisite course with minimum UOC requirement
        } else if (preg_match("/^([0-9]{1,3})_UOC$/", $coreq_conditions[$i], $matches)) {
            if ($user->getUOC() >= $matches[1]) {
                $coreq_evaluation[$i] = "true";
            } else {
                $coreq_evaluation[$i] = "false";
            }
        // Check individual corequisite course with minimum UNSW WAM requirement
        } else if (preg_match("/^([0-9]{1,3})_WAM$/", $coreq_conditions[$i], $matches)) {
            if ($user->getWAM() >= $matches[1]) {
                $coreq_evaluation[$i] = "true";
            } else {
                $coreq_evaluation[$i] = "false";
            }
        // Check individual corequisite course with program enrolment requirement
        } else if (preg_match("/^([0-9]{4})$/", $coreq_conditions[$i], $matches)) {
            if ($user->getProgram()->getCode() == $matches[1]) {
                $coreq_evaluation[$i] = "true";
            } else {
                $coreq_evaluation[$i] = "false";
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
            $coreq_evaluation[$i] = calculateUOCCourses($uoc_required, $regex, $courses_passed);
        // Check individual corequisite course with school approval requirement
        } else if (strcmp($coreq_conditions[$i], "SCHOOL_APPROVAL") == 0) {
            $coreq_evaluation[$i] = "false";
        // Other cases that are not handled yet
        } else if (preg_match("/^[A-Z_a-z0-9]+$/", $coreq_conditions[$i])) {
            $coreq_evaluation[$i] = "false";
        // Include brackets and other operators
        } else {
            $coreq_evaluation[$i] = $coreq_conditions[$i];
        }
    }

    $coreq_evaluation_string = implode(" ", $coreq_evaluation);
    $coreq_evaluation_string = str_ireplace("||", "|", $coreq_evaluation_string);
    $coreq_evaluation_string = str_ireplace("&&", "&", $coreq_evaluation_string);
    // echo $coreq_evaluation_string;
    if (preg_match("/(true|false)\s*\(/i", $coreq_evaluation_string)) {
        return -1;
    }

    return eval("return {$coreq_evaluation_string};");
}

// Check whether the given course can be taken based on its equivalence requirements.
// @param $course_to_check - course code to check (String)
// @param $courses_passed - courses passed (Course[])
// @param $user - current user (User)
// @return 1 if eligible
//         0 if ineligible
//         -1 if error
function checkEquiv($course_to_check, $courses_passed, $user) {
    global $courses;
    $equiv_evaluation = array();
    $key = $course_to_check . $user->getProgram()->getCareer();
    $equiv_conditions = explode(" ", $courses[$key]->getEquiv());

    // Check if the course to check has no equivalence
    if (count($equiv_conditions) <= 1) {
        return 0;
    }

    for ($i = 0; $i < count($equiv_conditions); $i++) {
        // Check individual equivalence course
        if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $equiv_conditions[$i])) {
            $equiv_evaluation[$i] = "false";
            if (array_key_exists($equiv_conditions[$i], $courses_passed)) {
                $equiv_evaluation[$i] = "true";
            }
        // Other cases that are not handled yet
        } else if (preg_match("/^[A-Z_a-z0-9]+$/", $equiv_conditions[$i])) {
            $equiv_evaluation[$i] = "false";
        // Include brackets and other operators
        } else {
            $equiv_evaluation[$i] = $equiv_conditions[$i];
        }
    }

    $equiv_evaluation_string = implode(" ", $equiv_evaluation);
    $equiv_evaluation_string = str_ireplace("||", "|", $equiv_evaluation_string);
    $equiv_evaluation_string = str_ireplace("&&", "&", $equiv_evaluation_string);
    if (preg_match("/(true|false)\s*\(/i", $equiv_evaluation_string)) {
        return -1;
    }

    return eval("return {$equiv_evaluation_string};");
}

// Check whether the given course can be taken based on its exclusion requirements.
// @param $course_to_check - course code to check (String)
// @param $courses_passed - courses passed (Course[])
// @param $user - current user (User)
// @return 1 if eligible
//         0 if ineligible
//         -1 if error
function checkExcl($course_to_check, $courses_passed, $user) {
    global $courses;
    $excl_evaluation = array();
    $key = $course_to_check . $user->getProgram()->getCareer();
    $excl_conditions = explode(" ", $courses[$key]->getExcl());

    // Check if the course to check has no exclusion
    if (count($excl_conditions) <= 1) {
        return 0;
    }

    for ($i = 0; $i < count($excl_conditions); $i++) {
        // Check individual equivalence course
        if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $excl_conditions[$i])) {
            $excl_evaluation[$i] = "false";
            if (array_key_exists($excl_conditions[$i], $courses_passed)) {
                $excl_evaluation[$i] = "true";
            }
        // Other cases that are not handled yet
        } else if (preg_match("/^[A-Z_a-z0-9]+$/", $excl_conditions[$i])) {
            $excl_evaluation[$i] = "false";
        // Include brackets and other operators
        } else {
            $excl_evaluation[$i] = $excl_conditions[$i];
        }
    }

    $excl_evaluation_string = implode(" ", $excl_evaluation);
    $excl_evaluation_string = str_ireplace("||", "|", $excl_evaluation_string);
    $excl_evaluation_string = str_ireplace("&&", "&", $excl_evaluation_string);
    if (preg_match("/(true|false)\s*\(/i", $excl_evaluation_string)) {
        return -1;
    }

    return eval("return {$excl_evaluation_string};");
}

// Check whether the user has taken the given course previously.
// @param $course_to_check - course code to check (String)
// @param $courses_passed - courses passed (Course[])
// @param $user - current user (User)
// @return 1 if taken
//         0 if not taken
function hasDoneCourse($course_to_check, $courses_passed, $user) {
    if (preg_match("/^([A-Z]{4}[0-9]{4})/", $course_to_check, $matches)) {
        if (array_key_exists($matches[1], $courses_passed)) {
            return 1;
        }
    }

    return 0;
}

// Check whether the user meets the minimum UOC for the course.
// @param $uoc_required - UOC required (int)
// @param $pattern - pattern to check (String)
// @param $courses_passed - courses passed (Course[])
// @return "true" if eligible
//         "false" if ineligible
function calculateUOCCourses($uoc_required, $pattern, $courses_passed) {
    $uoc_acquired = 0;
    $keys = array_keys($courses_passed);

    foreach ($keys as $key) {
        if (preg_match($pattern, $key)) {
            $uoc_acquired += $courses_passed[$key]->getUOC();
        }
    }

    if ($uoc_acquired >= $uoc_required) {
        return "true";
    } else {
        return "false";
    }
}

// Get the title of the given course.
// @param $code - course code (String)
// @return $title - course title (String)
function getTitleOfCourse($code) {
    global $courses;
    $title = null;

    foreach ($courses as $course) {
        if ($course == $code) {
            $title = $course->getTitle();
        }
    }

    return $title;
}

// Get the UOC of the given course.
// @param $code - course code (String)
// @return $uoc - course UOC (int)
function getUOCOfCourse($code) {
    global $courses;
    $uoc = null;

    foreach ($courses as $course) {
        if ($course == $code) {
            $uoc = $course->getUOC();
        }
    }

    return $uoc;
}

?>
