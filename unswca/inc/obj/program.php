<?php

class Program {
    var $code;
    var $title;
    var $career;
    var $uoc;

    function __construct($code, $title, $career, $uoc) {
        $this->code = $code;
        $this->title = $title;
        $this->career = $career;
        $this->uoc = $uoc;
    }

    function getCode() {
        return $this->code;
    }

    function getTitle() {
        return $this->title;
    }

    function getCareer() {
        return $this->career;
    }

    function getUOC() {
        return $this->uoc;
    }
}

?>
