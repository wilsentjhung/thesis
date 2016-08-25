<?php

class User {
    var $zid;
    var $given_name;
    var $family_name;
    var $uoc;
    var $wam;
    var $programs;
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
        $i = 0;
        $programs = array();
        $min_counter = $this->getMinCounter($zid, "program");
        $query = "SELECT * FROM
                  (SELECT pr.id, pr.code AS code, pr.title AS title, pr.career AS career, pr.uoc AS uoc, MAX(pre.term_id), COUNT(pr.id) AS counter
                  FROM people p, program_enrolments pre, programs pr
                  WHERE p.id = $zid AND p.id = pre.student_id AND pre.program_id = pr.id
                  GROUP BY pr.id ORDER BY pr.id) AS q WHERE q.counter = $min_counter";
        $result = pg_query($sims_db_connection, $query);
        while ($rows = pg_fetch_array($result)) {
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
            $programs[$i++] = $program;
        }

        // Construct StreamTaken object
        $i = 0;
        $streams = array();
        $min_counter = $this->getMinCounter($zid, "stream");
        $query = "SELECT * FROM
                  (SELECT pr.id, pr.code AS code, pr.title AS title, pr.career AS career, pr.uoc AS uoc, MAX(pre.term_id), COUNT(pr.id) AS counter
                  FROM people p, stream_enrolments pre, streams pr
                  WHERE p.id = $zid AND p.id = pre.student_id AND pre.stream_id = pr.id
                  GROUP BY pr.id ORDER BY pr.id) AS q WHERE q.counter = $min_counter";
        $result = pg_query($sims_db_connection, $query);
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
        $numerator = 0;
        $denominator = 0;
        $wam = 0;
        $uoc = 0;
        $query = "SELECT tr.code AS code, tr.title AS title, tr.mark AS mark, tr.grade AS grade, s.uoc AS uoc, tr.term AS term, t.id
                  FROM people p, transcript tr, subjects s, terms t
                  WHERE p.id = $zid AND p.id = tr.student_id AND tr.code LIKE s.code AND tr.term LIKE t.code
                  GROUP BY tr.code, tr.title, tr.mark, tr.grade, s.uoc, tr.term, t.id
                  ORDER BY t.id, tr.code";
        $result = pg_query($sims_db_connection, $query);
        while ($rows = pg_fetch_array($result)) {
            $code = str_ireplace(" ", "", $rows["code"]);
            $title = $rows["title"];
            $mark = str_ireplace(" ", "", $rows["mark"]);
            $grade = str_ireplace(" ", "", $rows["grade"]);
            $s_uoc = str_ireplace(" ", "", $rows["uoc"]);
            $term = str_ireplace(" ", "", $rows["term"]);

            $course = new CourseTaken($code, $title, $mark, $grade, $s_uoc, $term);
            $courses[$i++] = $course;

            // Calculate completed UOC and UNSW WAM
            if ($course->getOutcome() == 1) {
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
                $uoc += $course->getUOC();
            } else if ($course->getOutcome() == 2) {
                $numerator += $course->getMark()*$course->getUOC();
                $denominator += $course->getUOC();
            } else if ($course->getOutcome() == 3) {
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
        $this->programs = $programs;
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

    public function getPrograms() {
        return $this->programs;
    }

    public function getStreams() {
        return $this->streams;
    }

    public function getCourses() {
        return $this->courses;
    }

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
                    $remaining_req_courses = $req_courses;

                    foreach ($this->courses as $course) {
                        foreach ($req_courses as $req_course) {
                            if (strpos($req_course, $course->getCode()) !== false) {
                                $remaining_req_courses = removeArrayElements($remaining_req_courses, $req_course);
                            }
                        }
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

    // Get the requirements of a program or stream based on the given code
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

    private function getMinCounter($zid, $type) {
        include("inc/pgsql.php");
        $min_counter = 0;

        $query = "SELECT MIN(q.counter) AS min_counter FROM (SELECT COUNT(pr.id) AS counter
                  FROM people p, ${type}_enrolments pre, ${type}s pr
                  WHERE p.id = $zid AND p.id = pre.student_id AND pre.${type}_id = pr.id
                  GROUP BY pr.id ORDER BY pr.id) AS q";
        $result = pg_query($sims_db_connection, $query);
        $rows = pg_fetch_array($result);
        $min_counter = $rows["min_counter"];

        return $min_counter;
    }
}

?>
