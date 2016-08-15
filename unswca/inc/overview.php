<?php

echo "<h1 class='page-header'>" . $user->getGivenName() . " " . $user->getFamilyName() . " (z" . $user->getZID() . ")</h1>";

echo "<h2>Basic Information</h2>";

// Programs information
$i = 0;
echo "<div><table class='table table-striped table-hover'>";
echo "<thead><th>Program</th><th>Minimum UOC</th><th>Career</th></thead><tbody>";
foreach ($programs as $program) {
    echo "<tr data-toggle='collapse' data-target='#program-requirements" . $i . "' class='clickable'>";
    echo "<td class='col-md-6'>" . $program->getCode() . " - " . $program->getTitle() . "</td>";
    echo "<td class='col-md-3'>" . $program->getUOC() . "</td>";
    echo "<td class='col-md-3'>" . $program->getCareer() . "</td></tr>";
    echo "<tr><td colspan='3'><div id='program-requirements" . $i++ . "' class='collapse'>";
    echo "</div></td></tr>";
}
echo "</tbody></table></div>";

// Streams information
$i = 0;
echo "<div><table class='table table-striped table-hover'>";
echo "<thead><th>Stream</th><th>Minimum UOC</th><th>Career</th></thead><tbody>";
foreach ($streams as $stream) {
    echo "<tr data-toggle='collapse' data-target='#stream-requirements" . $i . "' class='clickable'>";
    echo "<td class='col-md-6'>" . $stream->getCode() . " - " . $stream->getTitle() . "</td>";
    echo "<td class='col-md-3'>" . $stream->getUOC() . "</td>";
    echo "<td class='col-md-3'>" . $stream->getCareer() . "</td></tr>";
    echo "<tr><td colspan='3'><div id='stream-requirements" . $i++ . "' class='collapse'>";
    echo "</div></td></tr>";
}
echo "</tbody></table></div>";

?>
