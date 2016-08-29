<?php

class Course {
    var $code;
    var $title;
    var $career;
    var $uoc;
    var $prereq;
    var $coreq;
    var $equiv;
    var $excl;

    public function __construct($code, $title, $career, $uoc, $prereq, $coreq, $equiv, $excl) {
        $this->code = $code;
        $this->title = $title;
        $this->career = $career;
        $this->uoc = $uoc;
        $this->prereq = $prereq;
        $this->coreq = $coreq;
        $this->equiv = $equiv;
        $this->excl = $excl;
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

    public function getPrereq() {
        return $this->prereq;
    }

    public function getCoreq() {
        return $this->coreq;
    }

    public function getEquiv() {
        return $this->equiv;
    }

    public function getExcl() {
        return $this->excl;
    }
}

?>
