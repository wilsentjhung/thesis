<?php

// Check if the given course is available as GenEd based on the program's faculty.
// @param $faculty - user's program's faculty (String)
// @param $course - course code to check (String)
// @return true if available
//         false if unavailable
function canBeGenEd($faculty, $course) {
    include("pgsql.php");

    $query = "SELECT c.can_be_gened AS can_be_gened, o.longname AS school, ofv.fac_name AS faculty
              FROM course_records c, orgunits o, org_faculty_view ofv
              WHERE LOWER(c.subject_area||c.catalogue_code) LIKE LOWER('$course') and c.responsible_acad_unit = o.id AND o.longname LIKE ofv.name";
    $result = pg_query($aims_db_connection, $query);
    $rows = pg_fetch_array($result);

    if ($rows["can_be_gened"] && $rows["faculty"] != $faculty) {
        return true;
    } else {
        return false;
    }
}

// Get the school and faculty responsible for the given program, stream or course code.
// @param $code - program, stream or course code (String)
// @return $result - DB result (require pg_fetch_array)
function getSchoolAndFaculty($code) {
    include("pgsql.php");
    $result = NULL;

    if (strlen($code) == 4) {           // Program code
        $query = "SELECT o.longname AS school, ofv.fac_name AS faculty
                  FROM program_records pr, orgunits o, org_faculty_view ofv
                  WHERE pr.code LIKE '$code' AND pr.acad_unit_responsible = o.id AND o.longname LIKE ofv.name
                  GROUP BY o.longname, ofv.fac_name";
        $result = pg_query($aims_db_connection, $query);
    } else if (strlen($code) == 6) {    // Stream code
        $query = "SELECT o.longname AS school, ofv.fac_name AS faculty
                  FROM stream_records st, orgunits o, org_faculty_view ofv
                  WHERE LOWER(st.subject_area||st.strand||st.stream_type) LIKE LOWER('$code') AND st.acad_unit_responsible = o.id AND o.longname LIKE ofv.name
                  GROUP BY o.longname, ofv.fac_name";
        $result = pg_query($aims_db_connection, $query);
    } else if (strlen($code) == 8) {    // Course code
        $query = "SELECT o.longname AS school, ofv.fac_name AS faculty
                  FROM course_records ct, orgunits o, org_faculty_view ofv
                  WHERE LOWER(ct.subject_area || ct.catalogue_code) LIKE LOWER('$code') AND ct.responsible_acad_unit = o.id AND o.longname LIKE ofv.name
                  GROUP BY o.longname, ofv.fac_name";
        $result = pg_query($aims_db_connection, $query);
    }

    return $result;
}

// Get the applicability of the given program.
// @param $program - program code (String)
// @return "S" if single award
//         "I" if dual award within the same faculty
//         "C" if dual award with different faculty
function getProgramApplicability($program) {
    include("pgsql.php");

    if (!isDualAward($program)) {
        return "S";
    } else {
        $i = 0;
        $programs = array();

        $query = "SELECT r.nss_id AS program_code
                  FROM program_records pr, dp_constituent_programs dp, records r
                  WHERE pr.code LIKE '$program' AND pr.id = dp.program AND dp.single_deg_prog = r.id";
        $result = pg_query($aims_db_connection, $query);
        while ($rows = pg_fetch_array($result)) {
            $programs[$i++] = $rows["program_code"];
        }

        $faculty1 = pg_fetch_array(getSchoolAndFaculty($programs[0]))["faculty"];
        $faculty2 = pg_fetch_array(getSchoolAndFaculty($programs[1]))["faculty"];

        if ($faculty1 == $faculty2) {
            return "I";
        } else {
            return "C";
        }
    }
}

// Get the single award programs of the given dual award program.
// @param $program - dual award program code (String)
// @return $single_award_programs - single award program codes (String[])
function findSingleAwardPrograms($program) {
    include("pgsql.php");
    $i = 0;
    $single_award_programs = array();

    if (isDualAward($program)) {
        $query = "SELECT r.nss_id AS program_code
                  FROM program_records pr, dp_constituent_programs dp, records r
                  WHERE pr.code LIKE '$program' AND pr.id = dp.program AND dp.single_deg_prog = r.id";
        $result = pg_query($aims_db_connection, $query);
        while ($rows = pg_fetch_array($result)) {
            $single_award_programs[$i++] = $rows["program_code"];
        }
    } else {
        $single_award_programs[$i++] = $program;
    }

    return $single_award_programs;
}

// Check if the given program is dual award.
// @param $program - program code to check (String)
// @return true if dual award
//         false if single award
function isDualAward($program) {
    include("pgsql.php");

    $query = "SELECT is_dual_award
              FROM program_records
              WHERE code LIKE '$program'";
    $result = pg_query($aims_db_connection, $query);
    $rows = pg_fetch_array($result);

    if ($rows["is_dual_award"] == "t") {
        return true;
    } else {
        return false;
    }
}

// Get the next term code given the term code.
// @param $term - term code (String)
// @return $next_term - next term code (String)
function getNextTerm($term) {
    $next_term = NULL;
    $year = intval(substr($term, 0, 2));
    $season = substr($term, 2, 1);
    $semester = intval(substr($term, 3, 1));

    if ($season == "x") {
        $year++;
        $season = "s";
        $semester = 1;
    } else if ($season == "s") {
        if ($semester == 1) {
            $season = "s";
            $semester = 2;
        } else if ($semester == 2) {
            $season = "x";
            $semester = 1;
        }
    }

    $next_term = $year . $season . $semester;

    return $next_term;
}

// Change the raw definition to be PHP-readable.
// @param $raw_defn - raw definition (String)
// @result $raw_defn - PHP-readable raw definition (String)
function toPHPRawDefn($raw_defn) {
    $raw_defn = str_ireplace("nil", "", $raw_defn);
    $raw_defn = str_ireplace("none", "", $raw_defn);
    $raw_defn = str_ireplace("#", ".", $raw_defn);
    $raw_defn = str_ireplace(";", "|", $raw_defn);
    $raw_defn = str_ireplace("{", "(", $raw_defn);
    $raw_defn = str_ireplace("}", ")", $raw_defn);

    return $raw_defn;
}

// Change the PostgreSQL-readable raw definition to be UI-readable.
// @param $raw_defn - raw definition (String)
// @result $raw_defn - PHP-readable raw definition (String)
function toUIRawDefn($raw_defn) {
    $raw_defn = str_ireplace(".", "#", $raw_defn);
    $raw_defn = str_ireplace("|", " or ", $raw_defn);
    $raw_defn = str_ireplace(",", ", ", $raw_defn);

    return $raw_defn;
}

// Remove specific elements from the given array.
// @param $array - target array
// @param $del_vals - values to be removed
// @param array_values($array) - modified array
function removeArrayElements($array, $del_vals) {
    if (is_array($del_vals)) {
         foreach ($del_vals as $del_key => $del_val) {
            foreach ($array as $key => $val) {
                if ($val == $del_val) {
                    unset($array[$key]);
                }
            }
        }
    } else {
        foreach ($array as $key => $val){
            if ($val == $del_vals) {
                unset($array[$key]);
            }
        }
    }

    return array_values($array);
}

?>
