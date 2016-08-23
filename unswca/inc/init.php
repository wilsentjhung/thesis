<?php

include("obj/user.php");
include("obj/program.php");
include("obj/stream.php");
include("obj/course.php");
include("obj/courseTaken.php");

// Construct Program object
$i = 0;
$programs = array("");
$query = "SELECT pr.id, pr.code AS code, pr.title AS title, pr.career AS career, pr.uoc AS uoc, MAX(pre.term_id)
          FROM people p, program_enrolments pre, programs pr
          WHERE p.id = $login_session AND p.id = pre.student_id AND pre.program_id = pr.id
          GROUP BY pr.id ORDER BY pr.id";
$result = pg_query($sims_db_connection, $query);
while ($rows = pg_fetch_array($result)) {
    $code = str_replace(" ", "", $rows["code"]);
    $title = $rows["title"];
    $career = str_replace(" ", "", $rows["career"]);
    $pr_uoc = str_replace(" ", "", $rows["uoc"]);

    $program = new Program($code, $title, $career, $pr_uoc);
    $programs[$i++] = $program;
}

// Construct Stream object
$i = 0;
$streams = array("");
$query = "SELECT st.id, st.code AS code, st.title AS title, st.career AS career, st.uoc AS uoc, MAX(ste.term_id)
          FROM people p, stream_enrolments ste, streams st
          WHERE p.id = $login_session AND p.id = ste.student_id AND ste.stream_id = st.id
          GROUP BY st.id ORDER BY st.id";
$result = pg_query($sims_db_connection, $query);
while ($rows = pg_fetch_array($result)) {
    $code = str_replace(" ", "", $rows["code"]);
    $title = $rows["title"];
    $career = str_replace(" ", "", $rows["career"]);
    $st_uoc = str_replace(" ", "", $rows["uoc"]);

    $stream = new Stream($code, $title, $career, $st_uoc);
    $streams[$i++] = $stream;
}

// Construct Course object
$i = 0;
$courses = array("");
$query = "SELECT p.course_code AS code, p.title AS title, p.uoc AS uoc, p.career AS career, p.norm_pre_req_conditions AS prereq, 
          c.norm_co_req_conditions AS coreq, q.norm_equivalence_conditions AS equivalence, 
          x.norm_exclusion_conditions AS exclusion 
          FROM pre_reqs p JOIN co_reqs c ON p.course_code = c.course_code AND p.career = c.career 
          JOIN equivalence q ON c.course_code = q.course_code AND c.career = q.career
          JOIN exclusion x ON q.course_code = x.course_code AND q.career = x.career";
$result = pg_query($aims_db_connection, $query);
while ($rows = pg_fetch_array($result)) {
  $course = new Course($rows["code"], $rows["title"], $rows["uoc"], $rows["career"], $rows["prereq"], $rows["coreq"], $rows["equivalence"], $rows["exclusion"]);
  $courses[$i++] = $course;

}

// Construct CourseTaken object
$i = 0;
$coursesTaken = array("");
$wam = 0;
$uoc = 0;
$query = "SELECT tr.code AS code, tr.title AS title, tr.mark AS mark, tr.grade AS grade, s.uoc AS uoc, tr.term AS term, t.id
          FROM people p, transcript tr, subjects s, terms t
          WHERE p.id = $login_session AND p.id = tr.student_id AND tr.code LIKE s.code AND tr.term LIKE t.code
          ORDER BY t.id, tr.code";
$result = pg_query($sims_db_connection, $query);
while ($rows = pg_fetch_array($result)) {
    $code = str_replace(" ", "", $rows["code"]);
    $title = $rows["title"];
    $mark = str_replace(" ", "", $rows["mark"]);
    $grade = str_replace(" ", "", $rows["grade"]);
    $s_uoc = str_replace(" ", "", $rows["uoc"]);
    $term = str_replace(" ", "", $rows["term"]);
    $outcome = checkCourseOutcome($mark, $grade);

    $courseTaken = new CourseTaken($code, $title, $mark, $grade, $s_uoc, $term, $outcome);
    $coursesTaken[$i++] = $courseTaken;

    // Calculate completed UOC and UNSW WAM
    if ($outcome == 1) {
        $wam += $courseTaken->getMark()*$courseTaken->getUOC();
        $uoc += $courseTaken->getUOC();
    }
}
$wam /= $uoc;
$wam = round($wam, 3);

// Construct User object
$query = "SELECT * FROM people WHERE id = $login_session";
$result = pg_query($sims_db_connection, $query);
$rows = pg_fetch_array($result);
$zid = str_replace(" ", "", $rows["id"]);
$given_name = $rows["given_name"];
$family_name = $rows["family_name"];
$user = new User($zid, $given_name, $family_name, $uoc, $wam);

?>
