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
        echo "<div id='term-{$i}-{$course->getTerm()}' class='term btn-group-vertical' role='group' style='background-color: #CCCCCC; border: 2px solid #CCCCCC;'><h5>{$course->getTerm()}</h5>";
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
    echo "<div id='term-{$i}-{$next_term}' class='term btn-group-vertical' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='background-color: lightblue; border: 2px solid lightblue;'><h5>{$next_term}</h5></div>";
    $i++;
}
$num_terms = $i;
echo "</div>";

// Show main progress bar =========================================================================
$done_uoc = $user->getProgram()->getUOC() - $user->getRemainingUOC();
$percentage = (100*$done_uoc)/$user->getProgram()->getUOC();
$percentageText = round($percentage, 2);
echo "<div class='progress'><div id='main-progress' class='progress-bar progress-bar-striped active' role='progressbar' aria-valuenow='{$done_uoc}' aria-valuemin='0' aria-valuemax='{$user->getProgram()->getUOC()}' style='width: {$percentage}%;'>$percentageText%</div></div>";

// Show progression-checker =======================================================================
$i = 0;
$num_requirements = 0;
// Show remaining CC requirements
echo "<div id='accordion' class='progression-checker' role='tablist' aria-multiselectable='true'>";
foreach ($user->getRemainingCCRequirements() as $raw_defn) {
    echo "<div class='panel-default'>";
    echo "<div id='accordion-heading-{$i}' class='panel-heading' role='tab' style='max-height: 50px;'><h4 class='panel-title'>";
    // Show requirement title and its own progress bar
    $done_uoc = $raw_defn->getMax() - $raw_defn->getMin();
    $percentage = (100*$done_uoc)/$raw_defn->getMax();
    echo "<a data-toggle='collapse' data-parent='#accordion' href='#accordion-content-{$i}' aria-expanded='false' aria-controls='accordion-content-{$i}'>";
    echo "{$raw_defn->getTitle()}";
    echo "</a></h4></div>";
    echo "<div class='progress' style='height: 10px;'><div id='progress-{$i}' class='progress-bar progress-bar-info' role='progressbar' aria-valuenow='{$done_uoc}' aria-valuemin='0' aria-valuemax='{$raw_defn->getMax()}' style='height: 10px; width: {$percentage}%;'></div></div>";
    // Show remaining courses for this requirement
    echo "<div id='accordion-content-{$i}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='accordion-heading-{$i}'>";
    echo "<div id='requirement-{$i}-{$raw_defn->getMax()}' class='requirement btn-group' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='min-height: 20px;'>";
    $j = 0;
    foreach ($raw_defn->getRawDefn() as $defn) {
        echo "<button type='button' id='cc-unit-{$i}-{$j}-{$defn->getUOC()}' class='btn btn-primary' draggable='true' ondragstart='drag(event)' style='border-radius: 0px; width: 200px;'>{$defn->getCode()}</button>";
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
    $done_uoc = $raw_defn->getMax() - $raw_defn->getMin();
    $percentage = (100*$done_uoc)/$raw_defn->getMax();
    echo "<a data-toggle='collapse' data-parent='#accordion' href='#accordion-content-{$i}' aria-expanded='false' aria-controls='accordion-content-{$i}'>";
    echo "{$raw_defn->getTitle()}";
    echo "</a></h4></div>";
    echo "<div class='progress' style='height: 10px;'><div id='progress-{$i}' class='progress-bar progress-bar-info' role='progressbar' aria-valuenow='{$done_uoc}' aria-valuemin='0' aria-valuemax='{$raw_defn->getMax()}' style='height: 10px; width: {$percentage}%;'></div></div>";
    // Show remaining courses for this requirement
    echo "<div id='accordion-content-{$i}' class='panel-collapse collapse' role='tabpanel' aria-labelledby='accordion-heading-{$i}'>";
    echo "<div id='requirement-{$i}-{$raw_defn->getMax()}' class='requirement btn-group' role='group' ondrop='drop(event)' ondragover='allowDrop(event)' style='min-height: 20px;'>";
    $j = 0;
    foreach ($raw_defn->getRawDefn() as $defn) {
        echo "<button type='button' id='pe-unit-{$i}-{$j}-{$defn->getUOC()}' class='btn btn-warning' draggable='true' ondragstart='drag(event)' style='border-radius: 0px; width: 200px;'>{$defn->getCode()}</button>";
        $j++;
    }
    echo "</div></div></div>";
    $i++;
}
$num_requirements = $i;
echo "</div></div>";

?>

<script>

// ================================================================================================
// Planner event handler ==========================================================================
// ================================================================================================
var coursePool = coursesPassed;
var numTerms = <?php echo json_encode($num_terms); ?>;
var numRequirements = <?php echo json_encode($num_requirements); ?>;

$(function () {
    $("[data-toggle='popover']").popover()
})

$(".popover-dismiss").popover({
    trigger: 'focus'
})

function drag(ev) {
    var parentId = $(ev.target).parent().attr("id");
    var unitId = ev.target.id;
    var unitText = ev.target.textContent;
    ev.dataTransfer.setData("text", parentId + "&" + unitId + "&" + unitText);
}

function allowDrop(ev) {
    ev.preventDefault();
}

function drop(ev) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("text");

    var parentId = data.split("&")[0];
    var parentIndex = parseInt(parentId.split("-")[1]);

    var unitId = data.split("&")[1];
    var unitFrom = parseInt(unitId.split("-")[2]);
    var unitUOC = parseInt(unitId.split("-")[4]);

    var unitText = data.split("&")[2];

    var mainProgressId = document.getElementById("main-progress").id;
    var mainProgressVal = parseInt($("#" + mainProgressId).attr("aria-valuenow"));

    var progressId = document.getElementById("progress-" + unitFrom).id;
    var progressVal  = parseInt($("#" + progressId).attr("aria-valuenow"));

    var targetId = ev.target.id;
    var targetIndex = parseInt(targetId.split("-")[1]);

    // If target ID to drop the dragged unit is term div
    if (targetId.includes("term")) {
        var max = parseInt(parentId.split("-")[2]);
        var termCode = targetId.split("-")[2];

        var maxNumCoursesInTerm = 0;
        var numCoursesInTerm = ev.target.childElementCount;

        if (termCode.includes("s")) {           // Normal semester
            maxNumCoursesInTerm = 5;
        } else if (termCode.includes("x")) {    // Summer semester
            maxNumCoursesInTerm = 2;
        }

        if (numCoursesInTerm <= maxNumCoursesInTerm) {
            // If unit is dragged from requirement div
            if (parentId.includes("requirement")) {
                mainProgressVal += unitUOC;
                var mainPercentage = (100*mainProgressVal)/requiredUOC;
                progressVal += unitUOC;
                var percentage = (100*progressVal)/max;

                if (progressVal >= 0 && progressVal <= max) {
                    var courseToCheck = unitText;
                    var termToCheck = termCode;
                    var coursesUpToTerm = getCoursesUpToTerm(termCode);

                    $.post("inc/checker.php", {course_to_check: courseToCheck, term_to_check: termToCheck, courses_up_to_term: coursesUpToTerm}).success(function(data) {
                        if (data == 1) {
                            $("#" + mainProgressId).attr("aria-valuenow", mainProgressVal).css("width", mainPercentage + "%");
                            $("#" + mainProgressId).text(mainPercentage.toFixed(2) + "%");
                            $("#" + progressId).attr("aria-valuenow", progressVal).css("width", percentage + "%");
                            ev.target.appendChild(document.getElementById(unitId));
                            coursePool.push(unitText + "-100-HD-" + termCode);
                        } else {
                            // TODO Feedback on required requirements
                        }
        			});
                }
            // If unit is dragged from term div
            } else if (parentId.includes("term")) {
                var oldTermCode = parentId.split("-")[2];
                removeCourseFromCoursePool(unitText + "-100-HD-" + oldTermCode);

                var courseToCheck = unitText;
                var termToCheck = termCode;
                var coursesUpToTerm = getCoursesUpToTerm(termCode);

                $.post("inc/checker.php", {course_to_check: courseToCheck, term_to_check: termToCheck, courses_up_to_term: coursesUpToTerm}).success(function(data) {
                    if (data == 1) {
                        ev.target.appendChild(document.getElementById(unitId));
                        coursePool.push(unitText + "-100-HD-" + termCode);
                    } else {
                        // TODO Feedback on required requirements
                        coursePool.push(unitText + "-100-HD-" + oldTermCode);
                    }
                });
            }
        }
    // If target ID to drop the dragged unit is requirement div
    } else if (targetId.includes("requirement")) {
        var max = parseInt(targetId.split("-")[2]);
        var termCode = parentId.split("-")[2];

        mainProgressVal -= unitUOC;
        var mainPercentage = (100*mainProgressVal)/requiredUOC;
        progressVal -= unitUOC;
        var percentage = (100*progressVal)/max;

        if (unitFrom == targetIndex) {
            if (progressVal >= 0 && progressVal <= max) {
                $("#" + mainProgressId).attr("aria-valuenow", mainProgressVal).css("width", mainPercentage + "%");
                $("#" + mainProgressId).text(mainPercentage.toFixed(2) + "%");
                $("#" + progressId).attr("aria-valuenow", progressVal).css("width", percentage + "%");
                ev.target.appendChild(document.getElementById(unitId));
                removeCourseFromCoursePool(unitText + "-100-HD-" + termCode);
            }
        }
    }
}

function checkCoursesEligibility() {
    for (var i = 0; i < coursePool.length; i++) {
        var code = coursePool[i].split("-")[0];
        var mark = coursePool[i].split("-")[1];
        var grade = coursePool[i].split("-")[2];
        var term = coursePool[i].split("-")[3];
        var coursesUpToTerm = getCoursesUpToTerm(term);
    }
}

// Get all courses from the course pool array (coursePool) that are taken up to the given term code included.
// @param term - term code (String)
// @return coursesUpToTerm - courses taken before the given term code (String[])
function getCoursesUpToTerm(term) {
    var coursesUpToTerm = [];

    for (var i = 0; i < coursePool.length; i++) {
        var termCode = coursePool[i].split("-")[3];

        if (whichTermMoreRecent(term, termCode) == 0 || whichTermMoreRecent(term, termCode) == 1) {
            coursesUpToTerm.push(coursePool[i]);
        }
    }

    return coursesUpToTerm;
}

// Get the next term code given the term code.
// @param term - term code (String)
// @return nextTerm - next term code (String)
function getNextTerm(term) {
    var nextTerm = null;
    var year = parseInt(term.substr(0, 2));
    var season = term.substr(2, 1);
    var semester = parseInt(term.substr(3, 1));

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

// Check which one of the given two term codes is more recent.
// @param term1 - first term code to check (String)
// @param term2 - second term code to check (String)
// @return 0 if term1 and term2 are the same
//         1 if term1 is more recent than term2
//         2 if term2 is more recent than term1
//         -1 if error
function whichTermMoreRecent(term1, term2) {
    var year1 = parseInt(term1.substr(0, 2));
    var season1 = term1.substr(2, 1);
    var semester1 = parseInt(term1.substr(3, 1));

    var year2 = parseInt(term2.substr(0, 2));
    var season2 = term2.substr(2, 1);
    var semester2 = parseInt(term2.substr(3, 1));

    if (term1 == term2) {
        return 0;
    } else {
        if (year1 == year2) {
            if (season1 == season2) {
                if (semester1 == semester2) {
                    return 0;
                } else {
                    if (semester1 > semester2) {
                        return 1;
                    } else  if (semester1 < semester2) {
                        return 2;
                    }
                }
            } else {
                if (season1 == "x" && season2 == "s") {
                    return 1;
                } else if (season1 == "s" && season2 == "x") {
                    return 2;
                }
            }
        } else {
            if (year1 > year2) {
                return 1;
            } else if (year1 < year2) {
                return 2;
            }
        }
    }

    return -1;
}

// Remove the given course from the course pool array (coursePool).
// @param course - course code to be removed (String)
function removeCourseFromCoursePool(course) {
    var index = coursePool.indexOf(course);
    if (index >= 0) {
        coursePool.splice(index, 1);
    }
}

</script>
