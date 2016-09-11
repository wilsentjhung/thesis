<?php

include("inc/pgsql.php");
include("inc/init.php");
include("inc/helper_functions.php");
include("inc/user_functions.php");
include("inc/obj/user.php");
$query = "SELECT DISTINCT student_id 
		  FROM full_details
          ORDER BY student_id";

$result = pg_query($sims_db_connection, $query);

while ($rows = pg_fetch_array($result)) {
	$zid = $rows["student_id"];
	$login_session = $zid;
//$zid = 1098725;
	echo "$zid";
	if (strcmp($zid, "2138285") == 0 || strcmp($zid, "2221228") == 0) {
		continue;
	}
	//going through each student
	$user = new User($zid, $courses);
	
	$career = $user->getProgram()->getCareer();
	//$psuedocareer = $user->getProgram()->getPsuedoCareer();
	echo " $career  ";

	$i = 0;
	$courses_passed = array();
	foreach ($user->getPassedCourses() as $course) {
    	$courses_passed[$i++] = "{$course->getCode()}-{$course->getMark()}-{$course->getGrade()}";
	}
	echo "<br>";
	check_details($user);
	echo "<br>";
	unset ($user);
}

function array_object_diff ($array, $course_to_exclude) {
	$result_array = array();
	foreach ($array as $a) {
		//echo $a->getCode();
		if (strcmp($a->getCode(), $course_to_exclude[0]->getCode()) != 0) {
			$result_array[count($result_array)] = $a;

		}
	}
	return $result_array;
}

function check_details ($user) {
	//select the course that student did in first semester and test if they were allowed to do it
	$start_term = $user->getCourses()[0]->getTerm();
	$end_term = $user->getCourses()[count($user->getCourses()) - 1]->getTerm();

	$current_passed_courses = array();
	$current_planned_courses = array();
	$current_term = $start_term;
	$current_course_counter = 0;
	while ($current_course_counter < count($user->getCourses())) {
		$current_term = $user->getCourses()[$current_course_counter]->getTerm();
		foreach ($current_planned_courses as $passed_course) {

			if ($passed_course->getOutcome() == 0 || $passed_course->getOutcome() == 1) {
				$current_passed_courses[$passed_course->getCode()] = $passed_course;
			}
		}
		//$current_passed_courses = array_merge($current_passed_courses, $current_planned_courses);
		$current_planned_courses = array();
		while ($current_course_counter < count($user->getCourses()) && strcmp($current_term, $user->getCourses()[$current_course_counter]->getTerm()) == 0) {
			$current_planned_courses[$user->getCourses()[$current_course_counter]->getCode()] = $user->getCourses()[$current_course_counter];
			$current_course_counter++;
		}

		//check the eligibility
		foreach ($current_planned_courses as $course_to_check) {
			$course_to_check_array = array();
			$course_to_check_array[0] = $course_to_check;
			$code = $course_to_check->getCode();
			$term = $course_to_check->getTerm();
			if (count($current_planned_courses)>1) {
				$outcome = checkEligibility($code, $current_passed_courses, array_object_diff($current_planned_courses, $course_to_check_array), $user);
			} else {
				$outcome = checkEligibility($code, $current_passed_courses, array(), $user);
			}
			echo "&nbsp;  &nbsp; $term  &nbsp;  $code  &nbsp;  $outcome<br>";
		}


		//$current_term = $user->getCourses()[$current_course_counter]->getTerm();

	}

	/*
	//last term
	foreach ($current_planned_courses as $passed_course) {
		if ($passed_course->getOutcome() == 0 || $passed_course->getOutcome() == 1) {
			$current_passed_courses[count($current_passed_courses)] = $passed_course;
		}
	}
	//$current_passed_courses = array_merge($current_passed_courses, $current_planned_courses);
	$current_planned_courses = array();
	while ($current_course_counter < count($user->getCourses()) && strcmp($current_term, $user->getCourses()[$current_course_counter]->getTerm()) == 0) {
		$current_planned_courses[count($current_planned_courses)] = $user->getCourses()[$current_course_counter];
		$current_course_counter++;
	}

	//check the eligibility
	foreach ($current_planned_courses as $course_to_check) {
		$course_to_check_array = array();
		$course_to_check_array[0] = $course_to_check;
		$code = $course_to_check->getCode();
		$term = $course_to_check->getTerm();
		if (count($current_planned_courses)>1) {
			$outcome = checkEligibility($code, $current_passed_courses, array_object_diff($current_planned_courses, $course_to_check_array), $user);
		} else {
			$outcome = checkEligibility($code, $current_passed_courses, array(), $user);
		}
		echo "&nbsp;  &nbsp; $term  &nbsp;  $code  &nbsp;  $outcome<br>";
	}
	*/
	//echo count($current_passed_courses);


}