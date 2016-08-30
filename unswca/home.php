<?php include("inc/session.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="img/favicon.ico">

    <!-- Include JQuery -->
    <script src="http://code.jquery.com/jquery-1.12.0.min.js"></script>
    <script src="http://code.jquery.com/jquery-migrate-1.2.1.min.js"></script>

    <title>UNSW Course Advisor</title>

    <!-- Bootstrap core CSS -->
    <link href="components/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Style for this template -->
    <link href="css/home_style.css" rel="stylesheet">

    <!-- Connect to the PostgreSQL databases -->
    <?php include("inc/pgsql.php"); ?>
    <!-- Courses initialisation -->
    <?php include("inc/courses_init.php"); ?>
    <!-- Include neccessary helper functions -->
    <?php include("inc/helper_functions.php"); ?>
    <!-- Include neccessary user functions -->
    <?php include("inc/user_functions.php"); ?>
    <!-- User initialisation -->
    <?php include("inc/user_init.php"); ?>
</head>

<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="#">UNSW Course Advisor</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="#">Help</a></li>
                    <li><a href="inc/logout.php">Log out</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Navigation sidebar -->
            <div class="col-sm-3 col-md-2 sidebar">
                <ul id="nav-sidebar" class="nav nav-sidebar">
                    <li id="overviewbtn" class="active"><a href="#">Overview</a></li>
                    <li id="reportbtn"><a href="#">Report</a></li>
                    <li id="handbookbtn"><a href="#">Handbook</a></li>
                    <li id="plannerbtn"><a href="#">Planner</a></li>
                </ul>
            </div>

            <!-- Overview content -->
            <div id="overview-content" class="overview-content col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <?php include("inc/overview.php"); ?>
            </div>

            <!-- Report content -->
            <div id="report-content" class="report-content col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <?php include("inc/report.php"); ?>
            </div>

            <!-- Handbook content -->
            <div id="handbook-content" class="handbook-content col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <!-- Buttons to switch between course, stream or program -->
                <div class="btn-group btn-group-justified" data-toggle="buttons">
                    <label class="handbookbtn btn btn-primary active">
                        <input type="radio" id="coursebtn" name="options" autocomplete="off" checked>Course
                    </label>
                    <label class="handbookbtn btn btn-primary">
                        <input type="radio" id="streambtn" name="options" autocomplete="off">Stream
                    </label>
                    <label class="handbookbtn btn btn-primary">
                        <input type="radio" id="programbtn" name="options" autocomplete="off">Program
                    </label>
                </div>
                <!-- Search box -->
                <div>
                    <input type="input" id="searchinput" class="searchinput" placeholder="Search...">
                    <div id="searchres" class="searchres"></div>
                </div>
                <!-- Info results -->
                <div id="info" class="info"></div>
            </div>

            <!-- Planner content -->
            <div id="planner-content" class="planner-content col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
                <?php include("inc/planner.php"); ?>
            </div>
        </div>
    </div>

    <!-- Navigation bar event handler -->
    <script src="inc/navbar.js"></script>
    <!-- Search box event handler -->
    <script src="inc/search.js"></script>

    <!-- Bootstrap core JavaScript -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script>window.jQuery || document.write("<script src='assets/js/vendor/jquery.min.js'><\/script>")</script>
    <script src="components/bootstrap-3.3.6-dist/js/bootstrap.min.js"></script>
    <!-- Include REDIPS -->
    <script src="components/REDIPS_drag/redips-drag-min.js"></script>
</body>
</html>
