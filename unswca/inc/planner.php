<?php

// ================================================================================================
// Show planner ===================================================================================
// ================================================================================================
echo "<div id='planner' class='planner'>";

// Show plan board ================================================================================
$i = 0;
$j = 0;
$term_num = 0;
$prev = NULL;
// Show past terms
echo "<div id='plan-board' class='plan-board'>";
foreach ($user->getCourses() as $course) {
    if ($prev != $course->getTerm()) {
        if ($i > 0) {
            echo "</div>";
        }
        echo "<div id='term-{$j}' class='term btn-group-vertical' role='group'><h5>{$course->getTerm()}</h5>";
        $j++;
    }
    if ($course->getOutcome() == 2) {   // Failed course
        echo "<button type='button' id='failed-unit-{$i}' class='btn btn-danger' draggable='false' style='border-radius: 0px; width: 200px;'>{$course->getCode()}</button>";
    } else {
        echo "<button type='button' id='passed-unit-{$i}' class='btn btn-success' draggable='false' style='border-radius: 0px; width: 200px;'>{$course->getCode()}</button>";
    }
    $prev = $course->getTerm();
    $i++;
}
echo "</div>";
// Show future terms
$i = 0;
$next_term = $prev;
while ($i < $user->getRemainingUOC()) {
    $next_term = getNextTerm($next_term);
    if (substr($next_term, 2, 1) == "s") {
        $i += 24;
    }
    echo "<div id='term-{$j}' class='term btn-group-vertical' role='group' ondrop='drop(event)' ondragover='allowDrop(event)'><h5>{$next_term}</h5></div>";
    $j++;
}
$term_num = $j;
echo "</div>";

// Show progression-checker =======================================================================
$i = 0;
$requirement_num = 0;
// Show remaining CC requirements
echo "<div id='accordion' class='progression-checker' role='tablist' aria-multiselectable='true'>";
foreach ($user->getRemainingCCRequirements() as $raw_defn) {
    echo "<div class='panel-default'>";
    echo "<div id='accordion-heading-{$i}' class='panel-heading' role='tab' style='max-height: 50px;'><h4 class='panel-title'>";
    // Show requirement title and its own progress bar
    $remaining_uoc = $raw_defn->getMax() - $raw_defn->getMin();
    echo "<a data-toggle='collapse' data-parent='#accordion' href='#accordion-content-{$i}' aria-expanded='false' aria-controls='accordion-content-{$i}'>";
    echo "{$raw_defn->getTitle()} ({$raw_defn->getRulT()}): {$remaining_uoc}/{$raw_defn->getMax()} UOC";
    echo "</a></h4></div>";
    $percentage = (100*$remaining_uoc)/$raw_defn->getMax();
    echo "<div class='progress' style='height: 10px;'><div id='progress-{$i}' class='progress-bar-info' role='progressbar' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100' style='height: 10px; width: {$percentage}%;'></div></div>";
    // Show remaining courses for this requirement
    echo "<div id='accordion-content-{$i}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='accordion-heading-{$i}'>";
    echo "<div id='requirement-{$i}' class='requirement btn-group' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='min-height: 20px;'>";
    $j = 0;
    foreach ($raw_defn->getRawDefn() as $defn) {
        echo "<button type='button' id='cc-unit-{$i}-{$j}' class='btn btn-primary' draggable='true' ondragstart='drag(event)' style='border-radius: 0px; width: 200px;'>{$defn->getCode()}</button>";
        $j++;
    }
    echo "</div></div></div>";
    $i++;
}
// Show remaining PE requirements
foreach ($user->getRemainingPERequirements() as $raw_defn) {
    echo "<div class='panel-default'>";
    echo "<div id='accordion-heading-{$i}' class='panel-heading' role='tab' style='max-height: 50px;'><h4 class='panel-title'>";
    // Show requirement title and its own progress bar
    $remaining_uoc = $raw_defn->getMax() - $raw_defn->getMin();
    echo "<a data-toggle='collapse' data-parent='#accordion' href='#accordion-content-{$i}' aria-expanded='false' aria-controls='accordion-content-{$i}'>";
    echo "{$raw_defn->getTitle()} ({$raw_defn->getRulT()}): {$remaining_uoc}/{$raw_defn->getMax()} UOC";
    echo "</a></h4></div>";
    $percentage = (100*$remaining_uoc)/$raw_defn->getMax();
    echo "<div class='progress' style='height: 10px;'><div id='progress-{$i}' class='progress-bar-info' role='progressbar' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100' style='height: 10px; width: {$percentage}%;'></div></div>";
    // Show remaining courses for this requirement
    echo "<div id='accordion-content-{$i}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='accordion-heading-{$i}'>";
    echo "<div id='requirement-{$i}' class='requirement btn-group' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='min-height: 20px;'>";
    $j = 0;
    foreach ($raw_defn->getRawDefn() as $defn) {
        echo "<button type='button' id='pe-unit-{$i}-{$j}' class='btn btn-warning' draggable='true' ondragstart='drag(event)' style='border-radius: 0px; width: 200px;'>{$defn->getCode()}</button>";
        $j++;
    }
    echo "</div></div></div>";
    $i++;
}
$requirement_num = $i;
echo "</div></div>";

?>

<script src="inc/planner.js">
    var termNum = <?php echo json_encode($user->getCourses()[0]->getTerm()); ?>;
    var requirementNum = <?php echo json_encode($user->getCourses()[count($user->getCourses()) - 1]->getTerm()); ?>;
</script>
