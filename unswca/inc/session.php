<?php

session_start();
include("pgsql.php");

$user_check = $_SESSION["login_user"];

$query = "SELECT * FROM people WHERE id = $user_check";
$result = pg_query($sims_db_connection, $query);
$rows = pg_fetch_array($result);

$login_session = $rows["id"];

if(!isset($login_session)){
    pg_close($sims_db_connection);
    header("Location: index.php");
}

?>
