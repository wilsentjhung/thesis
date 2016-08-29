<?php

include("pgsql.php");

if (isset($_POST["input"]) && isset($_POST["type"])) {
	$i = 0;
	$outputs = array();
	$input = $_POST["input"];
	$type = $_POST["type"];

	echo "<ul id='reslist' class='reslist'>";
	if ($type == "course") {
		$query = "SELECT * FROM course_records
                  WHERE  LOWER(subject_area||catalogue_code) LIKE LOWER('%$input%') OR LOWER(course_name) LIKE LOWER('%$input%')
		          ORDER BY (subject_area||catalogue_code)";
		$result = pg_query($aims_db_connection, $query);
		while ($rows = pg_fetch_array($result)) {
			$outputs[$i] = $rows["subject_area"] . $rows["catalogue_code"] . " - " . $rows["course_name"];
			echo "<br><li id='res" . $i . "'><a>" . $rows["subject_area"] . $rows["catalogue_code"] . " - " . $rows["course_name"] . "</a></li>";
			$i++;
		}
	} else if ($type == "stream") {
		$query = "SELECT * FROM stream_records
		          WHERE LOWER(subject_area||strand||stream_type) LIKE LOWER('%$input%') OR LOWER(stream_name) LIKE LOWER('%$input%')
		          ORDER BY (subject_area||strand||stream_type)";
		$result = pg_query($aims_db_connection, $query);
		while ($rows = pg_fetch_array($result)) {
			$outputs[$i] = $rows["subject_area"] . $rows["strand"] . $rows["stream_type"] . " - " . $rows["stream_name"];
			echo "<br><li id='res" . $i . "'><a>" . $rows["subject_area"] . $rows["strand"] . $rows["stream_type"] . " - " . $rows["stream_name"] . "</a></li>";
			$i++;
		}
	} else if ($type == "program") {
		$query = "SELECT * FROM program_records
		          WHERE LOWER(code) LIKE LOWER('%$input%') OR LOWER(program_name) LIKE LOWER('%$input%')
		          ORDER BY code, program_name";
		$result = pg_query($aims_db_connection, $query);
		while ($rows = pg_fetch_array($result)) {
			$outputs[$i] = $rows["code"] . " - " . $rows["program_name"];
			echo "<br><li id='res" . $i . "'><a>" . $rows["code"] . " - " . $rows["program_name"] . "</a></li>";
			$i++;
		}
	}
	echo "</ul>";
}

?>
