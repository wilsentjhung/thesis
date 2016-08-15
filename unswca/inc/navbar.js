// Overview navbar
$(document).ready(function() {
	$("#overviewbtn").click(function() {
    	$("#overviewbtn").addClass("active").siblings().removeClass("active");
    	$("#report-content").hide();
		$("#handbook-content").hide();
		$("#planner-content").hide();
    	$("#overview-content").show();
  	});
});

// Report navbar
$(document).ready(function() {
	$("#reportbtn").click(function() {
    	$("#reportbtn").addClass("active").siblings().removeClass("active");
		$("#overview-content").hide();
		$("#handbook-content").hide();
		$("#planner-content").hide();
    	$("#report-content").show();
 	});
});

// Handbook navbar
$(document).ready(function() {
	$("#handbookbtn").click(function() {
	    $("#handbookbtn").addClass("active").siblings().removeClass("active");
	    $("#overview-content").hide();
		$("#report-content").hide();
		$("#planner-content").hide();
		$("#handbook-content").show();
  	});
});

// Planner navbar
$(document).ready(function() {
	$("#plannerbtn").click(function() {
	    $("#plannerbtn").addClass("active").siblings().removeClass("active");
	    $("#overview-content").hide();
	    $("#report-content").hide();
	    $("#handbook-content").hide();
	    $("#planner-content").show();
  	});
});
