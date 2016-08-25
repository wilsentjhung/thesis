<?php

$i = 0;
$prev = NULL;

echo "<h2>My Plan</h2>";
echo "<div id='board' class='board'>";
/*foreach ($user->getCourses() as $course) {
    if ($prev != $course->getTerm()) {
        echo "<h3>" . $course->getTerm() . "</h3>";
    }

    $prev = $course->getTerm();
}*/
foreach ($user->getCourses() as $course) {
    if ($prev != $course->getTerm()) {
        if ($i == 0) {
            echo "<div id='" . $course->getTerm() . "' class='term btn-group-vertical'>";
        } else {
            echo "</div><div id='" . $course->getTerm() . "' class='term btn-group-vertical'>";
        }
    }

    echo "<button id='" . $course->getCode() ."' class='btn btn-success'>" . $course->getCode() . "</button>";

    $prev = $course->getTerm();
    $i++;
}
echo "</div></div>";

foreach ($user->getPrograms() as $program) {
    foreach ($program->getRequirements() as $requirement) {
        if ($requirement->getRulT() == "CC") {

        }
    }
}

/*foreach ($user->getStreams() as $stream) {
    $remaining_req_courses = getRemainingRequirements($stream->getRequirements(), $user->getCourses());

    foreach ($remaining_req_courses as $req_course) {
        echo $req_course . "<br>";
    }
}*/
foreach ($user->getRemainingRequirements() as $rr) {
    echo "<h2>" . $rr->getTitle() . " (" . $rr->getRulT() . ")</h2>";
    foreach ($rr->getRawDefn() as $r) {
        echo "<li id='" . $r . "'draggable='true'>" . $r . "</li>";
    }
    echo "<br><br>";
}

?>

<h1>Planning board using HTML 5 Drag & Drop</h1>
<div id="board" class="board">
    <div>
      <h3>Term 1</h3><h3>Term 2</h3><h3>Term 3</h3>
    </div>
    <ul id="todo" class="term">
        <li id="item1" draggable="true">Task 1</li>
        <li id="item2" draggable="true">Task 2</li>
    </ul>
    <ul id="inprogress" class="term"></ul>
    <ul id="done" class="term"></ul>
</div>
