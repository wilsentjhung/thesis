<?php

if (isset($_POST["course_to_check"]) && isset($_POST["courses_passed"])) {
    include("inc/session.php");
    include("inc/pgsql.php");
    include("inc/courses_init.php");
    include("inc/helper_functions.php");
    include("inc/user_functions.php");
    include("inc/user_reinit.php");

    $course_to_check = $_POST["course_to_check"];
    $courses_passed = $_POST["courses_passed"];
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
