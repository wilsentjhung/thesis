<?php

if (isset($_POST["course_to_check"]) && isset($_POST["term_to_check"]) && isset($_POST["courses_up_to_term"])) {
    session_start();

    include("helper_functions.php");
    include("user_functions.php");
    include("obj/course_taken.php");
    include("obj/stream_taken.php");
    include("obj/program_taken.php");
    include("obj/course.php");
    include("obj/user.php");

    $course_to_check = $_POST["course_to_check"];
    $term_to_check = $_POST["term_to_check"];
    $courses_up_to_term = $_POST["courses_up_to_term"];
    $user = unserialize($_SESSION["user"]);
    $courses = unserialize($_SESSION["courses"]);
    $i = 0;
    $courses_up_to_term_obj = array();
    $current_courses = array();

    foreach ($courses_up_to_term as $course) {
        $code = explode("-", $course)[0];
        $title = getTitleOfCourse($code, $user);
        $mark = explode("-", $course)[1];
        $grade = explode("-", $course)[2];
        $uoc = getUOCOfCourse($code, $user);
        $term = explode("-", $course)[3];

        $courses_up_to_term_obj[$code] = new CourseTaken($code, "", $title, $grade, $user->getProgram()->getCareer(), $uoc, $term);
        if ($term != $term_to_check) {
            $current_courses[$code] = new CourseTaken($code, "", $title, $grade, $user->getProgram()->getCareer(), $uoc, $term);
        }
    }

    $output = checkEligibility($course_to_check, $courses_up_to_term_obj, $current_courses, $user);

    echo json_encode($output);
}

?>
