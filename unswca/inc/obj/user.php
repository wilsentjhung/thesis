<?php

class User {
    var $zid;
    var $given_name;
    var $family_name;
    var $uoc;
    var $wam;

    public function __construct($zid, $given_name, $family_name, $uoc, $wam) {
        $this->zid = $zid;
        $this->given_name = $given_name;
        $this->family_name = $family_name;
        $this->uoc = $uoc;
        $this->wam = $wam;
    }

    public function getZID() {
        return $this->zid;
    }

    public function getGivenName() {
        return $this->given_name;
    }

    public function getFamilyName() {
        return $this->family_name;
    }

    public function getUOC() {
        return $this->uoc;
    }

    public function getWAM() {
        return $this->wam;
    }
}

?>
