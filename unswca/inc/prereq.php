 <?php
    //course_to_check is course code + career

    //suggest all courses the student is eligible to take
    function suggest1($user, $courses) {

        $keys = array_keys($courses);
        //key is course code + career
        foreach ($keys as $key) {
            if (strcmp($user->getProgram()->getCareer(), $courses[$key]->getCareer()) == 0) {
                get_eligibility($user, $key, $courses);
            }
        }

    }

    //WIP
    //suggest similar topics based on student course titles
    /*function suggest3($user, $courses) {
        $courses_passed = $user->getPassedCourses();
        foreach ($courses_passed  as $c) {
            $suggest_query = "SELECT s.title
                              FROM course_enrolments ce
                              JOIN courses c on ce.course_id = c.id
                              JOIN subjects s on c.subject_id = s.id
                              WHERE student_id = 3407134 AND
                              (grade = 'PC' OR grade = 'PS' OR grade = 'CR' OR grade = 'DN'
                                OR grade = 'HD' OR grade = 'SY')";
        $suggest_result = pg_query($sims_db_connection, $suggest_query);

        }

    }*/

    //WIP
    //suggest similar topics based on student course codes
    function suggest4($user, $courses) {
        $codes = array();
        $courses_passed = $user->getPassedCourses();
        foreach ($courses_passed as $c) {
            $codes[$c->getCode()] = 1;
        }


    }

    //suggest2($user, $courses);

    //echo $courses["COMP1917UG"]->getCode();


    /*SELECT s.title
    FROM course_enrolments ce
    JOIN courses c on ce.course_id = c.id
    JOIN subjects s on c.subject_id = s.id
    WHERE student_id = 3407134 AND
    (grade = 'PC' OR grade = 'PS' OR grade = 'CR' OR grade = 'DN' OR grade = 'HD' OR grade = 'SY')
    */
?>
