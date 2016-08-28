function drag(ev) {
    ev.dataTransfer.setData("text", ev.target.id);
}

function allowDrop(ev) {
    ev.preventDefault();
}

function drop(ev) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("text");

    if (ev.target.type != "button" && ev.target.childElementCount <= 5) {
        if (ev.target.id.includes("requirement") && data.includes(ev.target.id)) {
            ev.target.appendChild(document.getElementById(data));
        } else if (ev.target.id.includes("term")) {
            ev.target.appendChild(document.getElementById(data));
        }
    }
}
