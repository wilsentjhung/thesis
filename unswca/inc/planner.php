<?php

$i = 0;
$prev = NULL;

echo "<h2>My Plan</h2>";
echo "<div id='planner' class='planner'>";

echo "<div id='plan-board' class='plan-board'>";
foreach ($user->getCourses() as $course) {
    if ($prev != $course->getTerm()) {
        if ($i > 0) {
            echo "</div>";
        }
        echo "<div id='term {$course->getTerm()}' class='term btn-group-vertical' role='group'><h5>{$course->getTerm()}</h5>";
    }
    echo "<button type='button' id='done-unit {$course->getCode()}{$course->getTerm()}' class='btn btn-success' draggable='false' style='border-radius: 0px; width: 200px;'>{$course->getCode()}</button>";

    $prev = $course->getTerm();
    $i++;
}
echo "</div>";

$i = 0;
$next_term = $prev;

while ($i < $user->getRemainingUOC()) {
    $next_term = getNextTerm($next_term);
    if (substr($next_term, 2, 1) == "s") {
        $i += 24;
    }

    echo "<div id='term {$course->getTerm()}' class='term btn-group-vertical' role='group' ondrop='drop(event)' ondragover='allowDrop(event)'><h5>{$next_term}</h5></div>";
}
echo "</div>";

$i = 0;
echo "<div id='accordion' class='progression-checker' role='tablist' aria-multiselectable='true'>";
foreach ($user->getRemainingRequirements() as $raw_defn) {
    echo "<div class='panel-default'>";
    echo "<div id='accordion-heading{$i}' class='panel-heading' role='tab'><h4 class='panel-title'>";
    echo "<a data-toggle='collapse' data-parent='#accordion' href='#accordion-content{$i}' aria-expanded='false' aria-controls='accordion-content{$i}'>";
    echo "{$raw_defn->getTitle()} ({$raw_defn->getRulT()})</a></h4></div>";
    echo "<div id='accordion-content{$i}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='accordion-heading{$i}'>";
    echo "<div id='requirement{$i}' class='requirement btn-group' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='min-height: 20px;'>";
    foreach ($raw_defn->getRawDefn() as $req_course) {
        echo "<button type='button' id='planned-unit requirement{$i} {$req_course}' class='btn btn-success' draggable='true' ondragstart='drag(event)' style='border-radius: 0px; width: 200px;'>{$req_course}</button>";
    }
    echo "</div></div></div>";
    $i++;
}
echo "</div></div>";

?>
