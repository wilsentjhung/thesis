<?php

// Check the course outcome based on the given mark and grade
// Returns:
// 0 if active course
// 1 if passed course
// 2 if failed course
// 3 if not applicable course
function checkCourseOutcome($mark, $grade) {
    if ($mark == "" && $grade == "") {
        return 0; // Active course
    } else if ((is_numeric($mark) && $mark >= 50 && $grade != "UF") || $grade == "PC" || $grade == "SY") {
        return 1; // Passed course
    } else if ((is_numeric($mark) && $mark < 50 && $grade != "PC") || $grade == "FL" || $grade == "AF" || $grade == "UF") {
        return 2; // Failed course
    } else {
        return 3; // Not applicable course
    }
}

?>
