<?php

class User {
    var $zid;
    var $given_name;
    var $family_name;
    var $uoc;
    var $wam;
    var $program;
    var $streams;
    var $courses;

    public function __construct($zid) {
        include("inc/pgsql.php");
        include("program_taken.php");
        include("stream_taken.php");
        include("course_taken.php");
        include("requirement.php");

        $query = "SELECT * FROM people WHERE id = $zid";
        $result = pg_query($sims_db_connection, $query);
        $rows = pg_fetch_array($result);
        $zid = str_ireplace(" ", "", $rows["id"]);
        $given_name = $rows["given_name"];
        $family_name = $rows["family_name"];

        // Construct ProgramTaken object
        $result = $this->getTypeInfo($zid, "program");
        $rows = pg_fetch_array($result);
        $code = str_ireplace(" ", "", $rows["code"]);
        $title = $rows["title"];
        $career = str_ireplace(" ", "", $rows["career"]);
        $pr_uoc = str_ireplace(" ", "", $rows["uoc"]);

        // Check the school and faculty responsible for the program
        $school_faculty = pg_fetch_array(getSchoolAndFaculty($code));
        $school = $school_faculty["school"];
        $faculty = $school_faculty["faculty"];
        $requirements = $this->getRequirements($code);

        $program = new ProgramTaken($code, $title, $career, $pr_uoc, $school, $faculty, $requirements);

        // Construct StreamTaken object
        $i = 0;
        $streams = array();
        $result = $this->getTypeInfo($zid, "stream");
        while ($rows = pg_fetch_array($result)) {
            $code = str_ireplace(" ", "", $rows["code"]);
            $title = $rows["title"];
            $career = str_ireplace(" ", "", $rows["career"]);
            $st_uoc = str_ireplace(" ", "", $rows["uoc"]);

            // Check the school and faculty responsible for the stream
            $school_faculty = pg_fetch_array(getSchoolAndFaculty($code));
            $school = $school_faculty["school"];
            $faculty = $school_faculty["faculty"];
            $requirements = $this->getRequirements($code);

            $stream = new StreamTaken($code, $title, $career, $st_uoc, $school, $faculty, $requirements);
            $streams[$i++] = $stream;
        }

        // Construct CourseTaken object
        $i = 0;
        $courses = array();
        $numerator = 0;     // Numerator of the UNSW WAM calculation
        $denominator = 0;   // Denominator of the UNSW WAM calculation
        $wam = 0;
        $uoc = 0;
        $result = $this->getCourseInfo($zid);
        while ($rows = pg_fetch_array($result)) {
            $code = str_ireplace(" ", "", $rows["code"]);
            $title = $rows["title"];
            $mark = str_ireplace(" ", "", $rows["mark"]);
            $grade = str_ireplace(" ", "", $rows["grade"]);
            $career = str_ireplace(" ", "", $rows["career"]);
            $s_uoc = str_ireplace(" ", "", $rows["uoc"]);
            $term = str_ireplace(" ", "", $rows["term"]);

            $course = new CourseTaken($code, $title, $mark, $grade, $career, $s_uoc, $term);
            $courses[$i++] = $course;

            // Calculate completed UOC and UNSW WAM
            // UNSW WAM = sigma($mark*$uoc)/sigma($uoc)
            if ($course->getGrade() == "SY") {          // i.e. COMP4930 (Thesis Part A)
                $uoc += $course->getUOC();
            } else if ($course->getOutcome() == 1) {    // Passed course
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
                $uoc += $course->getUOC();
            } else if ($course->getOutcome() == 2) {    // Failed course
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
            } else if ($course->getOutcome() == 3) {    // i.e. exchange, research course
                $uoc += $course->getUOC();
            }
        }

        if ($uoc == 0) {
            $wam = 0;
        } else {
            $wam = $numerator/$denominator;
            $wam = round($wam, 3);
        }

        $this->zid = $zid;
        $this->given_name = $given_name;
        $this->family_name = $family_name;
        $this->uoc = $uoc;
        $this->wam = $wam;
        $this->program = $program;
        $this->streams = $streams;
        $this->courses = $courses;
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

    public function getProgram() {
        return $this->program;
    }

    public function getStreams() {
        return $this->streams;
    }

    public function getCourses() {
        return $this->courses;
    }

    // Get the courses passed by the user
    // @return $passed_courses - array of passed Course objects
    public function getPassedCourses() {
        $passed_courses = array();

        foreach ($this->courses as $course) {
            if ($course->getOutcome() == 1) {   // Passed course
                $key = $course->getCode() . $course->getCareer();
                $passed_courses[$key] = $course;
            }
        }

        return $passed_courses;
    }

    // Get the remaining requirements of the program or stream taken
    // @return $remaining_requirements - array of remaining Requirement objects
    public function getRemainingRequirements() {
        include("inc/pgsql.php");
        $i = 0;
        $remaining_requirements = array();
        $remaining_req_courses = array();

        foreach ($this->streams as $stream) {
            foreach ($stream->getRequirements() as $requirement) {
                if ($requirement->getRulT() == "CC") {
                    $min = $requirement->getMin();
                    $max = $requirement->getMax();
                    $req_courses = explode(",", $requirement->getRawDefn());
                    $remaining_req_courses = array_merge($remaining_req_courses, $req_courses);

                    foreach ($this->courses as $course) {
                        foreach ($req_courses as $req_course) {
                            if (strpos($req_course, $course->getCode()) !== false && $course->getOutcome() != 2) {
                                $remaining_req_courses = removeArrayElements($remaining_req_courses, $req_course);
                            }
                        }
                    }

                    for ($j = 0; $j < $i; $j++) {
                        $remaining_req_courses = removeArrayElements($remaining_req_courses, $remaining_requirements[$j]->getRawDefn());
                    }

                    if (!empty($remaining_req_courses)) {
                        $remaining_requirement = new Requirement($requirement->getRecT(), $requirement->getRulT(), $requirement->getTitle(), $requirement->getAppl(), $min, $max, $remaining_req_courses);
                        $remaining_requirements[$i++] = $remaining_requirement;
                    }
                }
            }
        }

        return $remaining_requirements;
    }

    // Get the requirements of a program or stream
    // @params $code - program or stream code
    // @return $requirements - array of Requirement objects
    private function getRequirements($code) {
        include("inc/pgsql.php");
        $i = 0;
        $requirements = array();

        $query = "SELECT rec_t, rul_t, title, appl, min, max, raw_defn
                  FROM active_rules
                  WHERE LOWER(code) LIKE LOWER('$code')
                  ORDER BY title";
        $result = pg_query($aims_db_connection, $query);
        while ($rows = pg_fetch_array($result)) {
            $rec_t = $rows["rec_t"];
            $rul_t = $rows["rul_t"];
            $title = $rows["title"];
            $appl = $rows["appl"];
            $min = $rows["min"];
            $max = $rows["max"];
            $raw_defn = toPGRawDefn($rows["raw_defn"]);

            $requirement = new Requirement($rec_t, $rul_t, $title, $appl, $min, $max, $raw_defn);
            $requirements[$i++] = $requirement;
        }

        return $requirements;
    }

    // Get the info of the program or course taken
    // @params $zid - zID
    // @params $type - either "program" or "stream"
    // @return $result - DB result (require pg_fetch_array)
    private function getTypeInfo($zid, $type) {
        include("inc/pgsql.php");
        $result = NULL;

        $min_counter = $this->getMinCounter($zid, $type);
        $query = "SELECT * FROM
                     (SELECT t.id, t.code AS code, t.title AS title, t.career AS career, t.uoc AS uoc, MAX(te.term_id), COUNT(t.id) AS counter
                     FROM people p, ${type}_enrolments te, ${type}s t
                     WHERE p.id = $zid AND p.id = te.student_id AND te.${type}_id = t.id
                     GROUP BY t.id ORDER BY t.id) AS q
                  WHERE q.counter = $min_counter";
        $result = pg_query($sims_db_connection, $query);

        return $result;
    }

    // Get the info of the course taken
    // @params $zid - zID
    // @return $result - DB result (require pg_fetch_array)
    private function getCourseInfo($zid) {
        include("inc/pgsql.php");
        $result = NULL;

        $query = "SELECT tr.code AS code, tr.title AS title, tr.mark AS mark, tr.grade AS grade, tr.career AS career, tr.uoc AS uoc, tr.term AS term, t.id
                  FROM transcript tr, terms t
                  WHERE tr.student_id = $zid AND tr.term LIKE t.code
                  GROUP BY tr.code, tr.title, tr.mark, tr.grade, tr.career, tr.uoc, tr.term, t.id
                  ORDER BY t.id, tr.code";
        $result = pg_query($sims_db_connection, $query);

        return $result;
    }

    // Get the minimum counter (fix SIMS bugs)
    // @params $zid - zID
    // @params $type - either "program" or "stream"
    // @return $min_counter
    private function getMinCounter($zid, $type) {
        include("inc/pgsql.php");
        $min_counter = 0;

        $query = "SELECT MIN(q.counter) AS min_counter FROM (SELECT COUNT(t.id) AS counter
                  FROM people p, ${type}_enrolments te, ${type}s t
                  WHERE p.id = $zid AND p.id = te.student_id AND te.${type}_id = t.id
                  GROUP BY t.id ORDER BY t.id) AS q";
        $result = pg_query($sims_db_connection, $query);
        $rows = pg_fetch_array($result);
        $min_counter = $rows["min_counter"];

        return $min_counter;
    }
}

?>
