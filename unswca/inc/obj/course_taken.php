<?php
class CourseTaken {
    var $code;
    var $title;
    var $mark;
    var $grade;
    var $uoc;
    var $term;
    var $outcome;

    public function __construct($code, $title, $mark, $grade, $uoc, $term) {
        $this->code = $code;
        $this->title = $title;
        $this->mark = $mark;
        $this->grade = $grade;
        $this->uoc = $uoc;
        $this->term = $term;
        $this->outcome = $this->checkCourseOutcome($mark, $grade);
    }

    public function getCode() {
        return $this->code;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getMark() {
        return $this->mark;
    }

    public function getGrade() {
        return $this->grade;
    }

    public function getUOC() {
        return $this->uoc;
    }

    public function getTerm() {
        return $this->term;
    }

    public function getOutcome() {
        return $this->outcome;
    }

    // Check the course outcome based on the given mark and grade
    // Returns:
    // 0 if active course
    // 1 if passed course
    // 2 if failed course
    // 3 if not applicable course
    private function checkCourseOutcome($mark, $grade) {
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
}

?>
