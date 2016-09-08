<?php

// ================================================================================================
// Show academic transcript =======================================================================
// ================================================================================================
echo "<h2>Academic Transcript</h2>";

echo "<div><table class='table table-striped'>";
echo "<thead><tr><th>#</th><th>Course Code</th><th>Course Title</th><th>Mark</th><th>Grade</th><th>UOC</th><th>Term</th></tr></thead>";
$i = 1;
foreach ($user->getCourses() as $course) {
    if ($course->getOutcome() == 0) {
        echo "<tbody><tr class='active'>";  // Active course
    } else if ($course->getOutcome() == 1) {
        echo "<tbody><tr class='success'>"; // Passed course
    } else if ($course->getOutcome() == 2) {
        echo "<tbody><tr class='danger'>";  // Failed course
    } else if ($course->getOutcome() == 3) {
        echo "<tbody><tr class='warning'>"; // Unrecorded course
    } else if ($course->getOutcome() == 4) {
        echo "<tbody><tr class='info'>";    // Other course
    }
    echo "<td class='col-sm-1'>" . $i++ . "</td>";
    echo "<td>{$course->getCode()}</td>";
    echo "<td>{$course->getTitle()}</td>";
    echo "<td>{$course->getMark()}</td>";
    echo "<td>{$course->getGrade()}</td>";
    echo "<td>{$course->getUOC()}</td>";
    echo "<td>{$course->getTerm()}</td>";
    echo "</tr></tbody>";
}

// Show completed UOC and UNSW WAM
echo "<tbody><tr><td>&#8226</td><td><b>Completed UOC:</b></td><td>{$user->getUOC()}</td><td></td><td></td><td></td><td></td></tr></tbody>";
echo "<tbody><tr><td>&#8226</td><td><b>UNSW WAM:</b></td><td>{$user->getWAM()}</td><td></td><td></td><td></td><td></td></tr></tbody>";
echo "</table></div>";

?>
