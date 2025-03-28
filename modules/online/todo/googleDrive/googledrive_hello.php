<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Drive API Quickstarte - App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Home Page description" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" />
    <style>
        body { padding-top: 70px; }
        h1 { margin-top: 0 }
        .app { display: flex; min-height: 100vh; flex-direction: column; }
        .app-content { flex: 1; }
        .app-footer { padding-bottom: 1em; }
    </style>
</head>
<body class="app">
<header class="app-header">
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/">
                    Application
                </a>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="/blog"><i class="glyphicon glyphicon-book"></i> Blog</a></li>
                    <li><a href="/about"><i class="glyphicon glyphicon-book"></i> About</a></li>
                    <li><a href="/cabinet"><i class="glyphicon glyphicon-user"></i> Cabinet2</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="app-content">
    <main class="container">
        <p>Drive API Quickstart 2</p>

        <!--Add buttons to initiate auth sequence and sign out-->
        <button id="authorize_button" style="display: none;">Authorize</button>
        <button id="signout_button" style="display: none;">Sign Out</button>

        <pre id="content" style="white-space: pre-wrap;"></pre>
    </main>
</div>

<footer class="app-footer">
    <div class="container">
        <hr />
        <p>&copy; 2017 - My App.</p>
    </div>
</footer>


<script src="js/googledrive_hello.js"></script>

<script async defer src="https://apis.google.com/js/api.js"
        onload="this.onload=function(){};handleClientLoad()"
        onreadystatechange="if (this.readyState === 'complete') this.onload()">
</script>


<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</body>
</html>
