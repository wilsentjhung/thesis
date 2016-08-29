var plan = [];

/*document.getElementById("accordion").onclick = function() {
    var courses = document.getElementById("requirement-0").firstChild.id;

    for (var course of courses) {
        plan.push(course.nodeValue);
    }

    alert(plan[0]);
};*/

function drag(ev) {
    var unitFrom = ev.target.id.split("-")[2];
    var progressId = document.getElementById("progress-" + unitFrom).id;
    var progressVal  = $("#" + progressId).attr("aria-valuenow");

    if (progressVal >= 0 && progressVal <= 100) {
        ev.dataTransfer.setData("text", ev.target.id);
    }
}

function allowDrop(ev) {
    ev.preventDefault();
}

function drop(ev) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("text");
    var unitFrom = data.split("-")[2];
    var requirementAt = ev.target.id.split("-")[1];
    var progressId = document.getElementById("progress-" + unitFrom).id;
    var progressVal  = $("#" + progressId).attr("aria-valuenow");

    if (ev.target.type != "button") {
        if (ev.target.id.includes("term") && ev.target.childElementCount <= 5) {
            $("#" + progressId).attr("aria-valuenow", progressVal + 6).css("width, progressVal + 6");
            ev.target.appendChild(document.getElementById(data));
        } else if (ev.target.id.includes("requirement")) {
            if (unitFrom == requirementAt) {
                $("#" + progressId).attr("aria-valuenow", progressVal + 6).css("width, progressVal + 6");
                ev.target.appendChild(document.getElementById(data));
            }
        }
        alert($("#" + progressId).attr("aria-valuenow"));
    }
}

function populatePlan(id) {
    /*var courses = document.getElementById(id).childNodes;

    for (var course of courses) {
        plan.push(course.text);
    }*/
}

// Get the next term code given the term code
// @param code - term code
// @return nextTerm - next term code
function getNextTerm(code) {
    var nextTerm = null;
    var year = parseInt(code.substr(0, 2));
    var season = code.substr(2, 1);
    var semester = parseInt(code.substr(3, 1));

    if (season == "x") {
        year++;
        season = "s";
        semester = 1;
    } else if (season == "s") {
        if (semester == 1) {
            season = "s";
            semester = 2;
        } else if ($semester == 2) {
            season = "x";
            semester = 1;
        }
    }

    nextTerm = year + season + semester;

    return nextTerm;
}
