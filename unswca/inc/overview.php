<?php

// ================================================================================================
// Show basic information =========================================================================
// ================================================================================================
echo "<h1 class='page-header'>{$user->getGivenName()} {$user->getFamilyName()} <small>z{$user->getZID()}</small></h1>";
echo "<h2>Basic Information</h2>";

// Show program information =======================================================================
echo "<div><table class='table table-striped table-hover'>";
echo "<thead><th>Program</th><th>School</th><th>Faculty</th><th>Career</th><th>UOC</th></thead><tbody>";
$program = $user->getProgram();
echo "<tr data-toggle='collapse' data-target='#program-requirements' class='clickable'>";
echo "<td>{$program->getCode()} - {$program->getTitle()}</td>";
echo "<td>{$program->getSchool()}</td>";
echo "<td>{$program->getFaculty()}</td>";
echo "<td>{$program->getCareer()}</td>";
echo "<td>{$program->getUOC()}</td></tr>";
// Show program requirements
$i = 1;
echo "<tr><td colspan='5'><div id='program-requirements' class='collapse'>";
echo "<div><table class='table table-striped'>";
echo "<thead><th>#</th><th>Title</th><th>Applicability</th><th>Type</th><th>Min</th><th>Max</th><th>Raw Definition</th></thead><tbody>";
foreach ($program->getRequirements() as $requirement) {
    echo "<tr><td>{$i}</td>";
    echo "<td>{$requirement->getTitle()}</td>";
    echo "<td>{$requirement->getAppl()}</td>";
    echo "<td>{$requirement->getRulT()}</td>";
    echo "<td>{$requirement->getMin()}</td>";
    echo "<td>{$requirement->getMax()}</td>";
    // Show raw definition
    $j = 0;
    $defn_list = array();
    foreach ($requirement->getRawDefn() as $defn) {
        $defn_list[$j++] = $defn->getCode();
    }
    echo "<td>" . toUIRawDefn(implode(",", $defn_list)) . "</td>";
    $i++;
}
echo "</tbody></table></div>";
echo "</div></td></tr>";
echo "</tbody></table></div>";

// Show stream information ========================================================================
$i = 0;
echo "<div><table class='table table-striped table-hover'>";
echo "<thead><th>Stream(s)</th><th>School</th><th>Faculty</th><th>Career</th><th>UOC</th></thead><tbody>";
foreach ($user->getStreams() as $stream) {
    echo "<tr data-toggle='collapse' data-target='#stream-requirements{$i}' class='clickable'>";
    echo "<td>{$stream->getCode()} - {$stream->getTitle()}</td>";
    echo "<td>{$stream->getSchool()}</td>";
    echo "<td>{$stream->getFaculty()}</td>";
    echo "<td>{$stream->getCareer()}</td>";
    echo "<td>{$stream->getUOC()}</td></tr>";
    // Show stream requirements
    $j = 1;
    echo "<tr><td colspan='5'><div id='stream-requirements{$i}' class='collapse'>";
    echo "<div><table class='table table-striped'>";
    echo "<thead><th>#</th><th>Title</th><th>Applicability</th><th>Type</th><th>Min</th><th>Max</th><th>Raw Definition</th></thead><tbody>";
    foreach ($stream->getRequirements() as $requirement) {
        echo "<tr><td>{$j}</td>";
        echo "<td>{$requirement->getTitle()}</td>";
        echo "<td>{$requirement->getAppl()}</td>";
        echo "<td>{$requirement->getRulT()}</td>";
        echo "<td>{$requirement->getMin()}</td>";
        echo "<td>{$requirement->getMax()}</td>";
        // Show raw definition
        $k = 0;
        $defn_list = array();
        foreach ($requirement->getRawDefn() as $defn) {
            $defn_list[$k++] = $defn->getCode();
        }
        echo "<td>" . toUIRawDefn(implode(",", $defn_list)) . "</td>";
        $j++;
    }
    echo "</tbody></table></div>";
    $i++;
}
echo "</tbody></table></div>";

// TESTING AND DEBUGGING vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// echo checkEligibility("COMP4121", $user->getPassedCourses());
// foreach ($user->recommendPopularCourses($courses) as $course) {
//    echo $course->getCode() . "<br>";
// }

?>
