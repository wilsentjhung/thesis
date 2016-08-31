<?php

// ================================================================================================
// Show planner ===================================================================================
// ================================================================================================
echo "<div id='planner' class='planner'>";

// Show plan board ================================================================================
$i = 0;
$j = 0;
$k = 0;
$l = 0;
$m = 0;
$num_terms = 0;
$prev = NULL;
// Show past terms
echo "<div id='plan-board' class='plan-board'>";
foreach ($user->getCourses() as $course) {
    if ($prev != $course->getTerm()) {
        if ($j > 0) {
            echo "</div>";
        }
        echo "<div id='term-{$i}' class='term btn-group-vertical' role='group' style='background-color: #CCCCCC; border: 2px solid #CCCCCC;'><h5>{$course->getTerm()}</h5>";
        $k = $i;
        $l = 0;
        $m = 0;
        $i++;
    }
    if ($course->getOutcome() == 2) {   // Failed course
        echo "<button type='button' id='failed-unit-{$k}-{$l}' class='btn btn-danger' data-toggle='popover' data-trigger='focus' title='{$course->getCode()} - {$course->getTitle()}' data-content='TEST' draggable='false' style='border-radius: 0px; width: 200px;'>{$course->getCode()}</button>";
        $l++;
    } else {
        echo "<button type='button' id='passed-unit-{$k}-{$m}' class='btn btn-success' data-toggle='popover' data-trigger='focus' title='{$course->getCode()} - {$course->getTitle()}' data-content='TEST' draggable='false' style='border-radius: 0px; width: 200px;'>{$course->getCode()}</button>";
        $m++;
    }
    $prev = $course->getTerm();
    $j++;
}
echo "</div>";
// Show future terms
$j = 0;
$next_term = $prev;
while ($j < $user->getRemainingUOC()) {
    $next_term = getNextTerm($next_term);
    if (substr($next_term, 2, 1) == "s") {
        $j += 24;
    }
    echo "<div id='term-{$i}' class='term btn-group-vertical' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='background-color: lightblue; border: 2px solid lightblue;'><h5>{$next_term}</h5></div>";
    $i++;
}
$num_terms = $i;
echo "</div>";

// Show progression-checker =======================================================================
$i = 0;
$num_requirements = 0;
// Show remaining CC requirements
echo "<div id='accordion' class='progression-checker' role='tablist' aria-multiselectable='true'>";
foreach ($user->getRemainingCCRequirements() as $raw_defn) {
    echo "<div class='panel-default'>";
    echo "<div id='accordion-heading-{$i}' class='panel-heading' role='tab' style='max-height: 50px;'><h4 class='panel-title'>";
    // Show requirement title and its own progress bar
    $remaining_uoc = $raw_defn->getMax() - $raw_defn->getMin();
    $percentage = (100*$remaining_uoc)/$raw_defn->getMax();
    echo "<a data-toggle='collapse' data-parent='#accordion' href='#accordion-content-{$i}' aria-expanded='false' aria-controls='accordion-content-{$i}'>";
    echo "{$raw_defn->getTitle()} ({$raw_defn->getRulT()})";
    echo "</a></h4></div>";
    echo "<div class='progress' style='height: 10px;'><div id='progress-{$i}' class='progress-bar-info' role='progressbar' aria-valuenow='{$remaining_uoc}' aria-valuemin='0' aria-valuemax='{$raw_defn->getMax()}' style='height: 10px; width: {$percentage}%;'></div></div>";
    // Show remaining courses for this requirement
    echo "<div id='accordion-content-{$i}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='accordion-heading-{$i}'>";
    echo "<div id='requirement-{$i}' class='requirement btn-group' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='min-height: 20px;'>";
    $j = 0;
    foreach ($raw_defn->getRawDefn() as $defn) {
        echo "<button type='button' id='cc-unit-{$i}-{$j}-{$defn->getUOC()}-{$raw_defn->getMax()}' class='btn btn-primary' data-toggle='popover' title='TEST' data-content='TEST' draggable='true' ondragstart='drag(event)' style='border-radius: 0px; width: 200px;'>{$defn->getCode()}</button>";
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
    $percentage = (100*$remaining_uoc)/$raw_defn->getMax();
    echo "<a data-toggle='collapse' data-parent='#accordion' href='#accordion-content-{$i}' aria-expanded='false' aria-controls='accordion-content-{$i}'>";
    echo "{$raw_defn->getTitle()} ({$raw_defn->getRulT()})";
    echo "</a></h4></div>";
    echo "<div class='progress' style='height: 10px;'><div id='progress-{$i}' class='progress-bar-info' role='progressbar' aria-valuenow='{$remaining_uoc}' aria-valuemin='0' aria-valuemax='{$raw_defn->getMax()}' style='height: 10px; width: {$percentage}%;'></div></div>";
    // Show remaining courses for this requirement
    echo "<div id='accordion-content-{$i}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='accordion-heading-{$i}'>";
    echo "<div id='requirement-{$i}' class='requirement btn-group' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='min-height: 20px;'>";
    $j = 0;
    foreach ($raw_defn->getRawDefn() as $defn) {
        echo "<button type='button' id='pe-unit-{$i}-{$j}-{$defn->getUOC()}-{$raw_defn->getMax()}' class='btn btn-warning' draggable='true' ondragstart='drag(event)' style='border-radius: 0px; width: 200px;'>{$defn->getCode()}</button>";
        $j++;
    }
    echo "</div></div></div>";
    $i++;
}
$num_requirements = $i;
echo "</div></div>";

?>

<script>
$(function () {
    $("[data-toggle='popover']").popover()
})
$(".popover-dismiss").popover({
  trigger: 'focus'
})
// ================================================================================================
// Planner event handler ==========================================================================
// ================================================================================================
var numTerms = <?php echo json_encode($num_terms); ?>;
var numRequirements = <?php echo json_encode($num_requirements); ?>;

function drag(ev) {
    ev.dataTransfer.setData("text", ev.target.id + "&" + ev.target.textContent);
}

function allowDrop(ev) {
    ev.preventDefault();
}

function drop(ev) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("text");
    var idData = data.split("&")[0];
    var textData = data.split("&")[1];
    var requirementAt = ev.target.id.split("-")[1];
    var unitFrom = idData.split("-")[2];
    var unitUOC = parseInt(idData.split("-")[4]);
    var max = parseInt(idData.split("-")[5]);
    var progressId = document.getElementById("progress-" + unitFrom).id;
    var progressVal  = parseInt($("#" + progressId).attr("aria-valuenow"));

    if (ev.target.type != "button") {
        if (ev.target.id.includes("term") && ev.target.childElementCount <= 5) {
            progressVal += unitUOC;
            var percentage = (100*progressVal)/max;

            if (progressVal >= 0 && progressVal <= max) {
                $.post("checker.php", {course_to_check: textData, courses_passed: coursesPassed}).success(function(data) {
                    if (data == 1) {
                        coursesPassed.push(textData + "-100-HD");
                        $("#" + progressId).attr("aria-valuenow", progressVal).css("width", percentage + "%");
                        ev.target.appendChild(document.getElementById(idData));
                    }
  				});
            }
        } else if (ev.target.id.includes("requirement")) {
            if (unitFrom == requirementAt) {
                progressVal -= unitUOC;
                var percentage = (100*progressVal)/max;

                if (progressVal >= 0 && progressVal <= max) {
                    $("#" + progressId).attr("aria-valuenow", progressVal).css("width", percentage + "%");
                    ev.target.appendChild(document.getElementById(idData));
                }
            }
        }
    }
}

// Get the next term code given the term code
// @param code - term code
// @return nextTerm - next term code
function getNextTerm(code) {
    var nextTerm = null;
    var year = parseInt(code.substr(0, 2));
    var season = code.substr(2, 1);
    var semester = parseInt(code.substr(3, 1));

    if (season == "x") {
        year++;
        season = "s";
        semester = 1;
    } else if (season == "s") {
        if (semester == 1) {
            season = "s";
            semester = 2;
        } else if ($semester == 2) {
            season = "x";
            semester = 1;
        }
    }

    nextTerm = year + season + semester;

    return nextTerm;
}

// Remove the given course from the coursesPassed array
// @param course - course code to be removed
function removeCourseFromCoursesPassed(course) {
    var index = coursesPassed.indexOf(course);
    if (index >= 0) {
        coursesPassed.splice(index, 1);
    }
}

</script>
