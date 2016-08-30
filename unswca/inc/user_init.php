<?php

include("obj/user.php");

// Construct User object
$user = new User($login_session, $courses);

$i = 0;
$courses_passed = array();
foreach ($user->getPassedCourses() as $course) {
    $courses_passed[$i++] = "{$course->getCode()}-{$course->getMark()}-{$course->getGrade()}";
}

?>

<script>
    var startTerm = <?php echo json_encode($user->getCourses()[0]->getTerm()); ?>;
    var currentTerm = <?php echo json_encode($user->getCourses()[count($user->getCourses()) - 1]->getTerm()); ?>;
    var coursesPassed = <?php echo json_encode($courses_passed); ?>;
</script>
