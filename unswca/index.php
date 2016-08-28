<?php
    include("inc/login.php");

    if (isset($_SESSION["login_user"])) {
        header("Location: home.php");
    }
?>

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
    <!-- Styles for this template -->
    <link href="css/index_style.css" rel="stylesheet">

    <!-- Connect to the PostgreSQL databases -->
    <?php include("inc/pgsql.php"); ?>
</head>

<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <a class="navbar-brand" href="#">UNSW Course Advisor</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <form action="" method="post" class="navbar-form navbar-right">
                    <div class="form-group">
                        <input type="text" name="zid" placeholder="zID" class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" class="form-control">
                    </div>
                    <button type="submit" name="submit" class="btn btn-success">Log in</button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div id="jumbotron" class="jumbotron">
        <div class="container">
            <h1>UNSW Course Advisor</h1>
            <p>UNSW Course Advisor is a tool that helps you with your degree requirements.</p>
            <p><a class="btn btn-primary btn-lg" href="#" role="button">Learn more &raquo;</a></p>
        </div>
    </div>

    <!-- Details container -->
    <div id="details" class="container">
        <div class="row">
            <div class="col-md-4">
                <h2>Progression Checks</h2>
                <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
                <p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p>
            </div>
            <div class="col-md-4">
                <h2>Degree Planner</h2>
                <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
                <p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p>
            </div>
            <div class="col-md-4">
                <h2>Course Recommender</h2>
                <p>Donec sed odio dui. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
                <p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p>
            </div>
        </div>

        <hr>
        <footer>
            <p>&copy; 2016 UNSW Course Advisor by <i>W. Tjhung</i> and <i>M. Simarta</i>.</p>
        </footer>
    </div> <!-- /container -->

    <!-- Bootstrap core JavaScript -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script>window.jQuery || document.write("<script src='assets/js/vendor/jquery.min.js'><\/script>")</script>
    <script src="components/bootstrap-3.3.6-dist/js/bootstrap.min.js"></script>
</body>
</html>
