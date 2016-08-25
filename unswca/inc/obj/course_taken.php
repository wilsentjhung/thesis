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
}

?>
