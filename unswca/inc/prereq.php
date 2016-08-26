<?php
    function calculate_uoc_courses ($user, $uoc_required, $pattern) {
        $k = 0;
        $uoc_acquired = 0;
        echo $pattern;
        $keys = array_keys($user->getPassedCourses());
        $courses_passed = $user->getPassedCourses();
        while ($k < count($keys)) {
            if (preg_match($pattern, $keys[$k])) {
                $uoc_acquired += $courses_passed[$keys[$k]]->getUOC(); 
            }
            $k++;

        }

        if ($uoc_acquired >= $uoc_required) {
            return "TRUE";
        }

        return "FALSE";

    }

    function has_done_course ($user, $course_to_check) {
        //$course_counter = 0;
        #checking if the person has passed the subject before
        //while ($course_counter < count($courses_passed)) {
            //echo $courses_passed[$course_counter];
            //echo $pre_req_condition[$i];
            //if (strcmp($courses_passed[$course_counter], $course_to_check) == 0) {
            //    return 1;
            //}
            //$course_counter++;
        //}

        $courses_passed = $user->getPassedCourses();
        if (array_key_exists($course_to_check, $courses_passed)) {
            return 1;
        }

        return 0;
    }

    //assumes the same index corresponds for courses_passed, marks and grades.
    //TODO degree type checking (MARKETING_HONOURS etc.)
    function check_pre_req ($user, $course_to_check, $courses) {
        $key = $course_to_check;// . $user->getProgram()->getCareer();
        //echo $key;
        $pre_req_condition = explode(" ", $courses[$key]->getPrereq());
        $i = 0;
        $pre_req_evaluation = array("");
        $courses_passed = $user->getPassedCourses();
        

        //no prereq
        if (count($pre_req_condition) <= 1) {
            return 1;
        }
        
        while ($i < count($pre_req_condition)) {


            //checking individual subject
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $pre_req_condition[$i])) {
                //$course_counter = 0;
                $pre_req_evaluation[$i] = "FALSE";
                //while (($course_counter < count($courses_passed)) && !(strcmp($pre_req_evaluation[$i], "TRUE") == 0)) {
                    
                    //if (strcmp($courses_passed[$course_counter], $pre_req_condition[$i]) == 0) {
                    if (array_key_exists($pre_req_condition[$i], $courses_passed)) {
                        $pre_req_evaluation[$i] = "TRUE";
                    } else {
                        if (array_key_exists($pre_req_condition[$i] . $user->getProgram()->getCareer(), $courses)) {
                            foreach ($courses_passed as $course_passed) {
                                if (strpos($courses[$pre_req_condition[$i] . $user->getProgram()->getCareer()]->getEquivalence(), $course_passed->getCode()) != false) {
                                //if (count(array_intersect_key($courses[$pre_req_condition[$i] . $user->getProgram()->getCareer()]->getEquivalence(), $courses_passed))>0) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                }
                            }
                        }
                    }

                    //} elseif (check_equiv_req($courses_passed[$course_counter], $courses_marks, $courses_grades, $pre_req_condition[$i], $uoc, $wam, $stream, $program_code, $career) > 0) {
                    //    $pre_req_evaluation[$i] = "TRUE";

                    //}
                //    $course_counter++;
                //}

            //checking individual subject with a minimum grade
            } elseif (preg_match("/^([A-Z]{4}[0-9]{4})\{([A-Z0-9]{2})\}$/", $pre_req_condition[$i], $matches)) {
                //$course_counter = 0;
                //while ($course_counter < count($courses_passed)) {
                    //if (strcmp($courses_passed[$course_counter], $pre_req_condition[$i])) {
                $pre_req_evaluation[$i] = "FALSE";
                if (array_key_exists($matches[1], $courses_passed)) {
                
                        if (preg_match("/^([0-9]{2})$/", $matches[2])) {
                            if ($courses_passed[$matches[1]]->getMark() >= $matches[2]) {
                                $pre_req_evaluation[$i] = "TRUE";
                            }

                        } else {
                            if (strcmp($matches[2], "PS") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "PS") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $pre_req_evaluation[$i] = "TRUE";

                                }
                            } elseif (strcmp($matches[2], "CR") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[2], "DN") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[2], "HD") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $pre_req_evaluation[$i] = "TRUE";
                                    
                                }
                            }
                        }

                    //}
                    //$course_counter++;
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
                if ($user->getProgram()->getCode() == $matches[1]) {
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
                $pre_req_evaluation[$i] = calculate_uoc_courses ($user, $uoc_required, $regex);


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
        echo $courses[$key]->getPrereq();

        echo $pre_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $pre_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . $pre_req_evaluation_string . ";");
    }

    function check_co_req ($user, $course_to_check, $courses) {
        $key = $course_to_check;// . $user->getProgram()->getCareer();
        //echo $key;
        $co_req_condition = explode(" ", $courses[$key]->getCoreq());
        $i = 0;
        $co_req_evaluation = array("");
        $courses_passed = $user->getPassedCourses();
        

        //no prereq
        if (count($co_req_condition) <= 1) {
            return 1;
        }
        
        while ($i < count($co_req_condition)) {


            //checking individual subject
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $co_req_condition[$i])) {
                //$course_counter = 0;
                $co_req_evaluation[$i] = "FALSE";
                //while (($course_counter < count($courses_passed)) && !(strcmp($co_req_evaluation[$i], "TRUE") == 0)) {
                    
                    //if (strcmp($courses_passed[$course_counter], $co_req_condition[$i]) == 0) {
                    if (array_key_exists($co_req_condition[$i], $courses_passed)) {
                        $co_req_evaluation[$i] = "TRUE";
                    } else {
                        if (array_key_exists($co_req_condition[$i] . $user->getProgram()->getCareer(), $courses)) {
                            foreach ($courses_passed as $course_passed) {
                                if (strpos($courses[$co_req_condition[$i] . $user->getProgram()->getCareer()]->getEquivalence(), $course_passed->getCode()) != false) {
                                //if (count(array_intersect_key($courses[$co_req_condition[$i] . $user->getProgram()->getCareer()]->getEquivalence(), $courses_passed))>0) {
                                    $co_req_evaluation[$i] = "TRUE";
                                }
                            }
                        }
                    }


                    //} elseif (check_equiv_req($courses_passed[$course_counter], $courses_marks, $courses_grades, $co_req_condition[$i], $uoc, $wam, $stream, $program_code, $career) > 0) {
                    //    $co_req_evaluation[$i] = "TRUE";

                    //}
                //    $course_counter++;
                //}

            //checking individual subject with a minimum grade
            } elseif (preg_match("/^([A-Z]{4}[0-9]{4})\{([A-Z0-9]{2})\}$/", $co_req_condition[$i], $matches)) {
                //$course_counter = 0;
                //while ($course_counter < count($courses_passed)) {
                    //if (strcmp($courses_passed[$course_counter], $co_req_condition[$i])) {
                $co_req_evaluation[$i] = "FALSE";
                if (array_key_exists($matches[1], $courses_passed)) {
                
                        if (preg_match("/^([0-9]{2})$/", $matches[2])) {
                            if ($courses_passed[$matches[1]]->getMark() >= $matches[2]) {
                                $co_req_evaluation[$i] = "TRUE";
                            }

                        } else {
                            if (strcmp($matches[2], "PS") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "PS") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $co_req_evaluation[$i] = "TRUE";

                                }
                            } elseif (strcmp($matches[2], "CR") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "CR") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $co_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[2], "DN") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "DN") == 0 || strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $co_req_evaluation[$i] = "TRUE";
                                    
                                }
                            } elseif (strcmp($matches[2], "HD") == 0) {
                                if (strcmp($courses_passed[$matches[1]]->getGrade(), "HD") == 0) {
                                    $co_req_evaluation[$i] = "TRUE";
                                    
                                }
                            }
                        }

                    //}
                    //$course_counter++;
                }

            //for uoc checking
            } elseif (preg_match("/^([0-9]{1,3})_UOC$/", $co_req_condition[$i], $matches)) {
                //echo "===";
                //echo "$uoc $matches[1]";
                //echo "===";
                
                if ($user->getUOC() >= $matches[1]) {
                    $co_req_evaluation[$i] = "TRUE";
                } else {
                    $co_req_evaluation[$i] = "FALSE";
                }


            //for wam checking
            } elseif (preg_match("/^([0-9]{1,3})_WAM$/", $co_req_condition[$i], $matches)) {
                if ($user->getWAM() >= $matches[1]) {
                    $co_req_evaluation[$i] = "TRUE";
                } else {
                    $co_req_evaluation[$i] = "FALSE";
                }

            //program code checking
            } elseif (preg_match("/^([0-9]{4})$/", $co_req_condition[$i], $matches)) {
                if ($user->getProgram()->getCode() == $matches[1]) {
                    $co_req_evaluation[$i] = "TRUE";
                } else {
                    $co_req_evaluation[$i] = "FALSE";
                }

            // UOC subject checking (12_UOC_LEVEL_1_CHEM, 6_UOC_LEVEL_1_BABS_BIOS)
            } elseif (preg_match("/^([0-9]{1,3})_UOC_LEVEL_([0-9])(_([A-Z]{4}))(_([A-Z]{4}))?$/", $co_req_condition[$i], $matches)) {
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
                $co_req_evaluation[$i] = calculate_uoc_courses ($user, $uoc_required, $regex);


            //school approval
            } elseif (strcmp($co_req_condition[$i], "SCHOOL_APPROVAL") == 0) {
                $co_req_evaluation[$i] = "FALSE";

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $co_req_condition[$i])) {
                $co_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                $co_req_evaluation[$i] = $co_req_condition[$i];

            }

            
            $i++;
        }


        $co_req_evaluation_string = implode(" ", $co_req_evaluation);
        echo $courses[$key]->getCoreq();

        echo $co_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $co_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . $co_req_evaluation_string . ";");
    }

    function check_equiv_req ($user, $course_to_check, $courses) {
        $key = $course_to_check;// . $user->getProgram()->getCareer();
        //echo $key;
        $equiv_req_condition = explode(" ", $courses[$key]->getEquivalence());
        $i = 0;
        $equiv_req_evaluation = array("");
        $courses_passed = $user->getPassedCourses();
        

        //no prereq
        if (count($equiv_req_condition) <= 1) {
            return 0;
        }
        
        while ($i < count($equiv_req_condition)) {


            //checking individual subject
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $equiv_req_condition[$i])) {
                //$course_counter = 0;
                $equiv_req_evaluation[$i] = "FALSE";
                //while (($course_counter < count($courses_passed)) && !(strcmp($equiv_req_evaluation[$i], "TRUE") == 0)) {
                    
                    //if (strcmp($courses_passed[$course_counter], $equiv_req_condition[$i]) == 0) {
                    if (array_key_exists($equiv_req_condition[$i], $courses_passed)) {
                        $equiv_req_evaluation[$i] = "TRUE";
                    }

                    //} elseif (check_equiv_req($courses_passed[$course_counter], $courses_marks, $courses_grades, $equiv_req_condition[$i], $uoc, $wam, $stream, $program_code, $career) > 0) {
                    //    $equiv_req_evaluation[$i] = "TRUE";

                    //}
                //    $course_counter++;
                //}

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $equiv_req_condition[$i])) {
                $equiv_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                $equiv_req_evaluation[$i] = $equiv_req_condition[$i];

            }

            
            $i++;
        }


        $equiv_req_evaluation_string = implode(" ", $equiv_req_evaluation);
        echo $courses[$key]->getCoreq();

        echo $equiv_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $equiv_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . $equiv_req_evaluation_string . ";");
    }

    function check_excl_req ($user, $course_to_check, $courses) {
        $key = $course_to_check;// . $user->getProgram()->getCareer();
        //echo $key;
        $excl_req_condition = explode(" ", $courses[$key]->getExclusion());
        $i = 0;
        $excl_req_evaluation = array("");
        $courses_passed = $user->getPassedCourses();
        

        //no exclusion
        if (count($excl_req_condition) <= 1) {
            return 0;
        }
        
        while ($i < count($excl_req_condition)) {


            //checking individual subject
            if (preg_match("/^[A-Z]{4}[0-9]{4}$/", $excl_req_condition[$i])) {
                //$course_counter = 0;
                $excl_req_evaluation[$i] = "FALSE";
                //while (($course_counter < count($courses_passed)) && !(strcmp($excl_req_evaluation[$i], "TRUE") == 0)) {
                    
                    //if (strcmp($courses_passed[$course_counter], $excl_req_condition[$i]) == 0) {
                    if (array_key_exists($excl_req_condition[$i], $courses_passed)) {
                        $excl_req_evaluation[$i] = "TRUE";
                    }

                    //} elseif (check_equiv_req($courses_passed[$course_counter], $courses_marks, $courses_grades, $excl_req_condition[$i], $uoc, $wam, $stream, $program_code, $career) > 0) {
                    //    $excl_req_evaluation[$i] = "TRUE";

                    //}
                //    $course_counter++;
                //}

            //things not handled yet
            } elseif (preg_match("/^[A-Z_a-z0-9]+$/", $excl_req_condition[$i])) {
                $excl_req_evaluation[$i] = "FALSE";
            } //for brackets and operators
            else {
                $excl_req_evaluation[$i] = $excl_req_condition[$i];

            }

            
            $i++;
        }


        $excl_req_evaluation_string = implode(" ", $excl_req_evaluation);
        echo $courses[$key]->getExclusion();

        echo $excl_req_evaluation_string;
        if (preg_match("/(FALSE|TRUE)\s*\(/i", $excl_req_evaluation_string)) {
            return -1;
        }
        return eval("return " . $excl_req_evaluation_string . ";");
    }

    function get_eligibility($user, $course_to_check, $courses) {
        
        echo "<h2>Courses</h2>";
        echo "<div><table class='table table-striped'>";
        echo "<thead><tr><th>Course</th><th>Info</th><th>Eligible</th></tr></thead>";
        echo "<tbody>";

        //while ($i < pg_num_rows($result)) {
        //    $course_result = pg_fetch_array($result);
            
            $check_this_course = $course_to_check;
            echo "<tr><td>" . $check_this_course . "</td>";
            echo "<td><br>";
            $test_has_done_course = has_done_course($user, $check_this_course);
            
            if ($test_has_done_course == 1) {
                echo "done = TRUE";
                $test_has_done_course = 1;
            } elseif ($test_has_done_course == -1) {
                echo "done = ERROR";
            } else {
                $test_has_done_course = 0;
                echo "done = FALSE";
            }
            echo $test_has_done_course;
            echo "<br>";

            $test_pre = check_pre_req($user, $check_this_course, $courses);

            if ($test_pre == 1) {
                echo "prereq = TRUE";
                $test_pre = 1;
            } elseif ($test_pre == -1) {
                echo "prereq = ERROR";
            } else {
                $test_pre = 0;
                echo "prereq = FALSE";
            }
            echo $test_pre;
            echo "<br>";

            $test_co = check_co_req($user, $check_this_course, $courses);

            if ($test_co == 1) {
                echo "coreq = TRUE";
                $test_co = 1;
            } elseif ($test_co == -1) {
                echo "coreq = ERROR";
            } else {
                $test_co = 0;
                echo "coreq = FALSE";
            }
            echo $test_co;
            echo "<br>";

            $test_equiv = check_equiv_req($user, $check_this_course, $courses);

            if ($test_equiv == 1) {
                echo "equivalence = TRUE";
                $test_equiv = 1;
            } elseif ($test_equiv == -1) {
                echo "equivalence = ERROR";
            } else {
                $test_equiv = 0;
                echo "equivalence = FALSE";
            }
            echo $test_equiv;
            echo "<br>";

            $test_excl = check_excl_req($user, $check_this_course, $courses);

            if ($test_excl == 1) {
                echo "exclusion = TRUE";
                $test_excl = 1;
            } elseif ($test_excl == -1) {
                echo "exclusion = ERROR";
            } else {
                $test_excl = 0;
                echo "exclusion = FALSE";
            }
            echo $test_excl;
            echo "<br>";
            //echo gettype($test_has_done_course);
            //echo gettype($test_pre);
            //echo gettype($test_co);
            //echo gettype($test_equiv);
            //echo gettype($test_excl);
            echo "return " . "(" . "(!(" . $test_has_done_course . "))&&" . $test_pre . "&&" . $test_co . "&&" . "(!(" .$test_equiv . "))&&" . "(!(" . $test_excl . ")))" . ";";
            echo "<br>";
            if (($test_has_done_course == -1) || ($test_pre == -1) || ($test_co == -1) || ($test_equiv == -1) || ($test_excl == -1)){
                $test_final = -1;
            } else {
                $test_final = eval("return " . "(" . "(!(" . $test_has_done_course . "))&&" . $test_pre . "&&" . $test_co . "&&" . "(!(" .$test_equiv . "))&&" . "(!(" . $test_excl . ")))" . ";");
            }
            if ($test_final == 1) {
                echo "final = TRUE";
            } elseif ($test_final == -1) {
                echo "final = ERROR";
            } else {
                $test_final = 0;
                echo "final = FALSE";
            }
            echo "</td>";
            echo "<td>$test_final</td></tr>";
            //$i++;
        //}
        echo "</tbody>";
        echo "</table></div>";

    }

    $keys = array_keys($courses);
    //key is course code + career
    foreach ($keys as $key) {
        if (strcmp($user->getProgram()->getCareer(), $courses[$key]->getCareer()) == 0) {
            get_eligibility($user, $key, $courses);
        }
    }
    
    //echo $courses["COMP1917UG"]->getCode();

?>