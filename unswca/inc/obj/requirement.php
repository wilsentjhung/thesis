<?php

class Requirement {
    var $rec_t;
    var $rul_t;
    var $title;
    var $appl;
    var $min;
    var $max;
    var $raw_defn;

    public function __construct($rec_t, $rul_t, $title, $appl, $min, $max, $raw_defn) {
        $this->rec_t = $rec_t;
        $this->rul_t = $rul_t;
        $this->title = $title;
        $this->appl = $appl;
        $this->min = $min;
        $this->max = $max;
        $this->raw_defn = $raw_defn;
    }

    public function getRecT() {
        return $this->rec_t;
    }

    public function getRulT() {
        return $this->rul_t;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getAppl() {
        return $this->appl;
    }

    public function getMin() {
        return $this->min;
    }

    public function getMax() {
        return $this->max;
    }

    public function getRawDefn() {
        return $this->raw_defn;
    }
}

?>
