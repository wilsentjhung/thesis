<?php

class CoursePool {
    var $courses;

    public function __construct($courses) {
        $this->courses = $courses;
    }

    public getCourses() {
        return $this->courses;
    }
}

?>
