<?php

// Check if the given course is available as GenEd based on the program's faculty
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

// Get the school and faculty responsible for the given program or stream code
function getSchoolAndFaculty($code) {
    include("pgsql.php");
    $result = NULL;

    if (strlen($code) == 4) {
        $query = "SELECT o.longname AS school, ofv.fac_name AS faculty
                  FROM program_records pr, orgunits o, org_faculty_view ofv
                  WHERE pr.code LIKE '$code' AND pr.acad_unit_responsible = o.id AND o.longname LIKE ofv.name
                  GROUP BY o.longname, ofv.fac_name";
        $result = pg_query($aims_db_connection, $query);
    } else if (strlen($code) == 6) {
        $query = "SELECT o.longname AS school, ofv.fac_name AS faculty
                  FROM stream_records st, orgunits o, org_faculty_view ofv
                  WHERE LOWER(st.subject_area||st.strand||st.stream_type) LIKE LOWER('$code') AND st.acad_unit_responsible = o.id AND o.longname LIKE ofv.name
                  GROUP BY o.longname, ofv.fac_name";
        $result = pg_query($aims_db_connection, $query);
    }

    return $result;
}

// Get the next term code given the term code
// @param $code - term code
// @return $next_term - next term code
function getNextTerm($code) {
    $next_term = NULL;
    $year = intval(substr($code, 0, 2));
    $season = substr($code, 2, 1);
    $semester = intval(substr($code, 3, 1));

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

// Change the raw definition to be system-readable
function toSystemRawDefn($raw_defn) {
    $raw_defn = str_ireplace("nil", "", $raw_defn);
    $raw_defn = str_ireplace("none", "", $raw_defn);
    $raw_defn = str_ireplace("#", "_", $raw_defn);
    $raw_defn = str_ireplace(";", "|", $raw_defn);
    $raw_defn = str_ireplace("{", "(", $raw_defn);
    $raw_defn = str_ireplace("}", ")", $raw_defn);

    return $raw_defn;
}

// Change the PostgreSQL-readable raw definition to be UI-readable
function toUIRawDefn($raw_defn) {
    $raw_defn = str_ireplace("_", "#", $raw_defn);
    $raw_defn = str_ireplace("|", " or ", $raw_defn);
    $raw_defn = str_ireplace(",", ", ", $raw_defn);

    return $raw_defn;
}

// Remove specific elements of the given array
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
