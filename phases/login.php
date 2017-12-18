<?php
	include_once('lib/csrf.php');
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>Login Page</title>
	</head>
	<body>
		<h1> Champion Shop Login</h1>
		<fieldset>
			<legend>Login Form</legend>
			<form id="loginForm" method="POST" action="auth-process.php?action=<?php echo ($action = 'login'); ?>">
			<label for="email">Email:</label>
			<input type="email" name="email" required="true" pattern="^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$" />
			<label for="pw">Password:</label>
			<input type="password" name="pw" required="true" pattern="^[A-Za-z_\d]\w{2,19}$" />
			<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
			<input type="submit" value="Login" />
			<p> Please use account: Richard@gmail.com and passwd: Richard to login as guest</p>
			</form>
		</fieldset>

		<h1>Find Password by Email</h1>
    <fieldset>
        <form method="POST" action="auth-process.php?action=<?php echo ($action = 'sendEmail'); ?>">
            <label for="Email">Input your email:</label>
            <input type="email" name="Email" required="true" pattern="^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$" />

            <input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
            <input type="submit" value="send email" />
        </form>
    </fieldset>

    <h1> New User Signup</h1>
		<fieldset>
			<legend>Signup Form</legend>
			<form id="SignupForm" method="POST" action="auth-process.php?action=<?php echo ($action = 'signup'); ?>">
			<label for="email">Email:</label>
			<input type="email" name="email" required="true" pattern="^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$" />
			<label for="pw">Password:</label>
			<input type="password" name="pw" required="true" pattern="^[A-Za-z_\d]\w{2,19}$" />
			<input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
			<input type="submit" value="Sign up" />
			</form>
		</fieldset>

    <p>
        <a href="index.php">Cancel</a>&emsp;
        <a href="login.php">Login</a>
				<br>
				<br>
				Extensions: <br>6.8 (Passwd Change)  <br>6.10 (AJAX)  <br>6.15 (Passwd Reset) <br>6.16 (Member Management)
    </p>
	</body>
</html>
