$(document).ready(function() {
	$("#searchinput").keyup(function() {
  		var input = $(this).val();

		if (input != "") {
			if ($("#coursebtn").is(":checked")) {
	            $.post("inc/search.php", {input: input, type: "course"}, function(data) {
					if (input.length > 3) {
						$("#searchres").html(data);
						$("#searchres").show();
					}
  				});
			} else if ($("#streambtn").is(":checked")) {
				$.post("inc/search.php", {input: input, type: "stream"}, function(data) {
					if (input.length > 3) {
						$("#searchres").html(data);
						$("#searchres").show();
					}
  				});
			} else if ($("#programbtn").is(":checked")) {
				$.post("inc/search.php", {input: input, type: "program"}, function(data) {
					if (input.length > 3) {
						$("#searchres").html(data);
						$("#searchres").show();
					}
  				});
			}
		} else {
			$("#searchres").hide();
		}
    });
});
