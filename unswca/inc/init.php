<?php

include("inc/pgsql.php");
include("obj/course.php");
include("obj/user.php");

// Construct Course object
$i = 0;
$courses = array();
$query = "SELECT p.course_code AS code, p.title AS title, p.career AS career, p.uoc AS uoc, p.norm_pre_req_conditions AS prereq, c.norm_co_req_conditions AS coreq, q.norm_equivalence_conditions AS equiv, x.norm_exclusion_conditions AS excl
          FROM pre_reqs p JOIN co_reqs c ON p.course_code = c.course_code AND p.career = c.career
          JOIN equivalence q ON c.course_code = q.course_code AND c.career = q.career
          JOIN exclusion x ON q.course_code = x.course_code AND q.career = x.career";
$result = pg_query($aims_db_connection, $query);
while ($rows = pg_fetch_array($result)) {
    $course = new Course($rows["code"], $rows["title"], $rows["career"], $rows["uoc"], $rows["prereq"], $rows["coreq"], $rows["equiv"], $rows["excl"]);
    $key = $course->getCode() . $course->getCareer();
    $courses[$key] = $course;
}

// Construct User object
$user = new User($login_session, $courses);

$i = 0;
$courses_passed = array();
foreach ($user->getPassedCourses() as $course) {
    $courses_passed[$i++] = "{$course->getCode()}-{$course->getMark()}-{$course->getGrade()}-{$course->getTerm()}";
}

$_SESSION["courses"] = serialize($courses);
$_SESSION["user"] = serialize($user);

?>

<script>
    var startTerm = <?php echo json_encode($user->getCourses()[0]->getTerm()); ?>;
    var currentTerm = <?php echo json_encode($user->getCourses()[count($user->getCourses()) - 1]->getTerm()); ?>;
    var requiredUOC = <?php echo json_encode($user->getProgram()->getUOC()); ?>;
    var coursesPassed = <?php echo json_encode($courses_passed); ?>;
</script>
