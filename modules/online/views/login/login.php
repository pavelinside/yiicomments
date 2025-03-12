<?php
$KEY = 'some secret key';
if (!empty($_GET['url']) && !empty($_GET['key']) && md5($_GET['url'] . $KEY) === $_GET['key']) {
	header('HTTP/1.0 301 Moved Permanently');
	header('Location: ' . $_GET['url']);
	exit();
} else {
//	header('HTTP/1.0 404 Not Found');
//	echo 'No location or bad key specified.';
}
?>
<!DOCTYPE HTML>
<HTML>
<head>
	<style>
		html {
			background: linear-gradient(to top, #58afff, #7dfffd 10%, #feca3f);
			height: 100%;
		}

		.field {
			clear: both;
			text-align: right;
			line-height: 75px;
		}

		.main, .field label {
			float: left
		}

		.main {
			text-align: center;
			padding-left: 40%;
			padding-top: 10%;
		}

		.form {
			margin: auto;
		}

		.error {
			color: red;
		}

		body {
			background-color: silver;
		}
	</style>
</head>

<body>
<div class="main">
	<form class="form" method="post" action="">
		<div class="field">
			<label for="n">Login</label>
			<input name="login" autofocus value="<?php echo $login; ?>"/>
		</div>
		<div class="field">
			<label for="ln">Password</label>
			<input type="password" name="password" value=""/>
		</div>
		<input type="submit" value="Войти"/>
		<p class="error"><?php echo $error; ?></p>
	</form>
</div>

</body>
</HTML>
