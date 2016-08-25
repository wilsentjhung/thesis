<?php
    function calculate_uoc_courses ($courses_passed, $uoc_required, $pattern) {
        $k = 0;
        $uoc_acquired = 0;
        echo $pattern;
        while ($k < count($courses_passed)) {
            if (preg_match($pattern, $courses_passed[$k])) {
                $uoc_acquired += $courses_passed[$k].getUOC(); 
            }
            $k++;

        }

        if ($uoc_acquired >= $uoc_required) {
            return "TRUE";
        }

        return "FALSE";

    }

    function has_done_course ($courses_passed, $course_to_check) {
        $course_counter = 0;
        #checking if the person has passed the subject before
        while ($course_counter < count($courses_passed)) {
            //echo $courses_passed[$course_counter];
            //echo $pre_req_condition[$i];
            if (strcmp($courses_passed[$course_counter], $course_to_check) == 0) {
                return 1;
            }
            $course_counter++;
        }

        return 0;
    }

    //assumes the same index corresponds for courses_passed, marks and grades.
    //TODO UOC subject checking
    //TODO degree type checking (MARKETING_HONOURS etc.)
    function check_pre_req ($courses_passed, $courses_marks, $courses_grades, $course_to_check) {
        
        $pre_req_condition = explode(" ", $course_to_check.getPrereq());
        $i = 0;
        $pre_req_evaluation = array("");
        
        

        //no prereq
        if (count($pre_req_condition) <= 1) {
            return 1;
        }
        
        while ($i < count($pre_req_condition)) {


            //checking individual subject
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $pre_req_condition[$i])) {
                $course_counter = 0;
                $pre_req_evaluation[$i] = "FALSE";
                while (($course_counter < count($courses_passed)) && !(strcmp($pre_req_evaluation[$i], "TRUE") == 0)) {
                    
                    if (strcmp($courses_passed[$course_counter], $pre_req_condition[$i]) == 0) {
                        $pre_req_evaluation[$i] = "TRUE";

                    //} elseif (check_equiv_req($courses_passed[$course_counter], $courses_marks, $courses_grades, $pre_req_condition[$i], $uoc, $wam, $stream, $program_code, $career) > 0) {
                    //    $pre_req_evaluation[$i] = "TRUE";

                    }
                    $course_counter++;
                }

            //checking individual subject with a minimum grade
            } elseif (preg_match("/^[A-Z]{4}[0-9]{4}\{([A-Z0-9]{2})\}$/", $pre_req_condition[$i], $matches)) {
                $course_counter = 0;
                while ($course_counter < count($courses_passed)) {
                    if (strcmp($courses_passed[$course_counter], $pre_req_condition[$i])) {
                        if (preg_match("/^[A-Z]{4}[0-9]{4}\{([0-9]{1,3})\}$/", $matches[1])) {
                            if ($courses_marks[$course_counter] >= $matches[1]) {
                                $pre_req_evaluation[$i] = "TRUE";
                            }

                        } else {
                            if (strcmp($matches[1], "PS")) {
                                if (strcmp($courses_grades[$course_counter], "PS") || strcmp($courses_grades[$course_counter], "CR") || strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";

                                }
                            } elseif (strcmp($matches[1], "CR")) {
                                if (strcmp($courses_grades[$course_counter], "CR") || strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[1], "DN")) {
                                if (strcmp($courses_grades[$course_counter], "DN") || strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[1], "HD")) {
                                if (strcmp($courses_grades[$course_counter], "HD")) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            }
                        }

                    }
                    $course_counter++;
                }
                if (!strcmp($pre_req_evaluation[$i], "TRUE")) {
                    $pre_req_evaluation[$i] = "FALSE";
                }

            //for uoc checking
            } elseif (preg_match("/^([0-9]{1,3})_UOC$/", $pre_req_condition[$i], $matches)) {
                //echo "===";
                //echo "$uoc $matches[1]";
                //echo "===";
                
                if ($user->getUOC() >= $matches[1]) {
                    $pre_req_evaluation[$i] = "TRUE";
                } else {
                    $pre_req_evaluation[$i] = "FALSE";
                }


            //for wam checking
            } elseif (preg_match("/^([0-9]{1,3})_WAM$/", $pre_req_condition[$i], $matches)) {
                if ($user->getWAM() >= $matches[1]) {
                    $pre_req_evaluation[$i] = "TRUE";
                } else {
                    $pre_req_evaluation[$i] = "FALSE";
                }

            //program code checking
            } elseif (preg_match("/^([0-9]{4})$/", $pre_req_condition[$i], $matches)) {
                if ($program->getCode() == $matches[1]) {
                    $pre_req_evaluation[$i] = "TRUE";
                } else {
                    $pre_req_evaluation[$i] = "FALSE";
                }

            // UOC subject checking (12_UOC_LEVEL_1_CHEM, 6_UOC_LEVEL_1_BABS_BIOS)
            } elseif (preg_match("/^([0-9]{1,3})_UOC_LEVEL_([0-9])(_([A-Z]{4}))(_([A-Z]{4}))?$/", $pre_req_condition[$i], $matches)) {
                $uoc_required = $matches[1];
                $j = 4;
                $regex = "/^(";
                while ($j < count($matches)) {
                    if ($j > 4) {
                        $regex .= "|";
                    }
                    $regex .= $matches[$j];
                    $regex .= $matches[2];
                    $regex .= "...";
                    $j += 2;
                }
                $regex .= ")$/";
                $pre_req_evaluation[$i] = calculate_uoc_courses ($courses_passed, $uoc_required, $regex);


            //school approval
            } elseif (strcmp($pre_req_condition[$i], "SCHOOL_APPROVAL") == 0) {
                $pre_req_evaluation[$i] = "FALSE";

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $pre_req_condition[$i])) {
                $pre_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                $pre_req_evaluation[$i] = $pre_req_condition[$i];

            }

            
            $i++;
        }


        $pre_req_evaluation_string = implode(" ", $pre_req_evaluation);
        echo $pre_req_result[0];

        echo $pre_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $pre_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . $pre_req_evaluation_string . ";");
    }

?>