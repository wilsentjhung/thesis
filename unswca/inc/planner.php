<?php

$i = 0;
$prev = NULL;

echo "<h2>My Plan</h2>";
echo "<div id='planner' class='planner'>";

echo "<div id='board' class='board'>";
foreach ($user->getCourses() as $course) {
    if ($prev != $course->getTerm()) {
        if ($i == 0) {
            echo "<div id='" . $course->getTerm() . "' class='term btn-group-vertical' role='group'><h5>" . $course->getTerm() . "</h5>";
        } else {
            echo "</div><div id='" . $course->getTerm() . "' class='term btn-group-vertical' role='group'><h5>" . $course->getTerm() . "</h5>";
        }
    }

    echo "<button type='button' class='unit btn btn-success' style='border-radius: 0px; width: 200px;'>" . $course->getCode() . "</button>";

    $prev = $course->getTerm();
    $i++;
}
echo "</div></div>";

echo "<div class='progression-checker'>";
foreach ($user->getRemainingRequirements() as $raw_defn) {
    echo "<div id='" . $raw_defn->getTitle() . "' class='requirement btn-group'>";
    echo "<h2>" . $raw_defn->getTitle() . " (" . $raw_defn->getRulT() . ")</h2>";
    foreach ($raw_defn->getRawDefn() as $requirement) {
        echo "<button type='button' class='unit btn btn-success' style='border-radius: 0px; width: 200px;'>" . $requirement . "</button>";
    }
    echo "</div><br>";
}
echo "</div></div>";

?>
