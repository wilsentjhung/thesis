<?php

if (isset($_POST["course_to_check"]) && isset($_POST["courses_passed"])) {
    session_start();

    include("helper_functions.php");
    include("user_functions.php");
    include("obj/course_taken.php");
    include("obj/stream_taken.php");
    include("obj/program_taken.php");
    include("obj/course.php");
    include("obj/user.php");

    $course_to_check = $_POST["course_to_check"];
    $courses_passed = $_POST["courses_passed"];
    $user = unserialize($_SESSION["user"]);
    $courses = unserialize($_SESSION["courses"]);
    $i = 0;
    $courses_passed_obj = array();

    foreach ($courses_passed as $course_passed) {
        $code = explode("-", $course_passed)[0];
        $title = getTitleOfCourse($code);
        $mark = explode("-", $course_passed)[1];
        $grade = explode("-", $course_passed)[2];
        $uoc = getUOCOfCourse($code);
        $courses_passed_obj[$code] = new CourseTaken($code, "", $title, $grade, $user->getProgram()->getCareer(), $uoc, "");
    }

    $output = checkEligibility($course_to_check, $courses_passed_obj, $user);

    echo json_encode($output);
}

?>
