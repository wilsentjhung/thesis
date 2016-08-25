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
        $this->outcome = checkCourseOutcome($mark, $grade);
    }
    function getCode() {
        return $this->code;
    }
    function getTitle() {
        return $this->title;
    }
    function getMark() {
        return $this->mark;
    }
    function getGrade() {
        return $this->grade;
    }
    function getUOC() {
        return $this->uoc;
    }
    function getTerm() {
        return $this->term;
    }
    function getOutcome() {
        return $this->outcome;
    }
}
?>
