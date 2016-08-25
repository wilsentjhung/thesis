<?php

class CourseTaken {
    var $code;
    var $title;
    var $uoc;
    var $prereq;
    var $coreq;
    var $equiv;
    var $excl;


    public function __construct($code, $title, $uoc, $prereq, $coreq, $equiv, $excl, $career) {
        $this->code = $code;
        $this->title = $title;
        $this->uoc = $uoc;
        $this->prereq = $prereq;
        $this->coreq = $coreq;
        $this->equiv = $equiv;
        $this->excl = $excl;
        $this->career = $career
    }

    public function getCode() {
        return $this->code;
    }

    public function getTitle() {
        return $this->title;
    }

    function getUOC() {
        return $this->uoc;
    }

    function getPrereq() {
        return $this->prereq;
    }

    function getCoreq() {
        return $this->coreq;
    }

    function getEquivalence() {
        return $this->equiv;
    }

    function getExclusion() {
        return $this->excl;
    }

    function getCareer() {
        return $this->career;
    }
}

?>
