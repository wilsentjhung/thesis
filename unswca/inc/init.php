<?php

include("inc/pgsql.php");
include("obj/user.php");
include("obj/course.php");

// Construct User object --------------------------------------------------------------------------
$user = new User($login_session);

// Construct Course object ------------------------------------------------------------------------
$i = 0;
$courses = array();
$query = "SELECT p.course_code AS code, p.title AS title, p.uoc AS uoc, p.career AS career, p.norm_pre_req_conditions AS prereq,
          c.norm_co_req_conditions AS coreq, q.norm_equivalence_conditions AS equivalence,
          x.norm_exclusion_conditions AS exclusion
          FROM pre_reqs p JOIN co_reqs c ON p.course_code = c.course_code AND p.career = c.career
          JOIN equivalence q ON c.course_code = q.course_code AND c.career = q.career
          JOIN exclusion x ON q.course_code = x.course_code AND q.career = x.career";
$result = pg_query($aims_db_connection, $query);
while ($rows = pg_fetch_array($result)) {
  $course = new Course($rows["code"], $rows["title"], $rows["uoc"], $rows["career"], $rows["prereq"], $rows["coreq"], $rows["equivalence"], $rows["exclusion"]);
  $key = $course->getCode() . $course->getCareer();
  $courses[$key] = $course;
}


?>
