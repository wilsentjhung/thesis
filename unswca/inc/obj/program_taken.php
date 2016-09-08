<?php

class ProgramTaken {
    var $code;
    var $title;
    var $career;
    var $uoc;
    var $school;
    var $faculty;
    var $requirements;
    var $is_dual_award;

    public function __construct($code, $title, $career, $uoc, $school, $faculty, $requirements, $is_dual_award) {
        $this->code = $code;
        $this->title = $title;
        $this->career = $career;
        $this->uoc = $uoc;
        $this->school = $school;
        $this->faculty = $faculty;
        $this->requirements = $requirements;
        $this->is_dual_award = $is_dual_award;
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

    /*public function getPsuedoCareer() {
        if ($this->career != "UG") {
            return "PG";
        } else {
            return "UG";
        }
    }*/

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

    public function getIsDualAward() {
        return $this->is_dual_award;
    }
}

?>
