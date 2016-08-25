<?php

class StreamTaken {
    var $code;
    var $title;
    var $career;
    var $uoc;
    var $school;
    var $faculty;
    var $requirements;

    public function __construct($code, $title, $career, $uoc, $school, $faculty, $requirements) {
        $this->code = $code;
        $this->title = $title;
        $this->career = $career;
        $this->uoc = $uoc;
        $this->school = $school;
        $this->faculty = $faculty;
        $this->requirements = $requirements;
    }

    public function getCode() {
        return $this->code;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getCareer() {
        return $this->career;
    }

    public function getUOC() {
        return $this->uoc;
    }

    public function getSchool() {
        return $this->school;
    }

    public function getFaculty() {
        return $this->faculty;
    }

    public function getRequirements() {
        return $this->requirements;
    }
}

?>
