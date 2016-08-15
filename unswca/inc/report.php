<?php

echo "<h2>Academic Transcript</h2>";
echo "<div><table class='table table-striped'>";
echo "<thead><tr><th>#</th><th>Course Code</th><th>Course Title</th><th>Mark</th><th>Grade</th><th>UOC</th><th>Term</th></tr></thead>";
$i = 1;
foreach ($courses as $course) {
    $outcome = checkCourseOutcome($course->getMark(), $course->getGrade());
    if ($outcome == 0) {
        echo "<tbody><tr class='active'>";  // Active course
    } else if ($outcome == 1) {
        echo "<tbody><tr class='success'>"; // Passed course
    } else if ($outcome == 2) {
        echo "<tbody><tr class='warning'>"; // Failed course
    } else if ($outcome == 3) {
        echo "<tbody><tr class='info'>";    // Not applicable course
    }

    echo "<td>" . $i++ . "</td>";
    echo "<td>" . $course->getCode() . "</td>";
    echo "<td>" . $course->getTitle() . "</td>";
    echo "<td>" . $course->getMark() . "</td>";
    echo "<td>" . $course->getGrade() . "</td>";
    echo "<td>" . $course->getUOC() . "</td>";
    echo "<td>" . $course->getTerm() . "</td>";
    echo "</tr></tbody>";
}
echo "<tbody><tr><td><b>Completed UOC:</b></td><td><td>" . $user->getUOC() . "</td><td></td><td></td><td></td><td></td></tr></tbody>";
echo "<tbody><tr><td><b>UNSW WAM:</b></td><td><td>" . $user->getWAM() . "</td><td></td><td></td><td></td><td></td></tr></tbody>";
echo "</table></div>";

echo "<h2>Remaining Academic Requirements</h2>";

?>
