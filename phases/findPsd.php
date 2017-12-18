<?php
    include_once('lib/csrf.php');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Find Password</title>
</head>
<body>
    <h1>Find Password by Email</h1>
    <fieldset>
        <form method="POST" action="auth-process.php?action=<?php echo ($action = 'sendEmail'); ?>">
            <label for="Email">Input your email:</label>
            <input type="email" name="Email" required="true" pattern="^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$" />

            <input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
            <input type="submit" value="send email" />
        </form>
    </fieldset>

    <p>
        <a href="index.php">Cancel</a>&emsp;
        <a href="login.php">Login</a>
    </p>

</body>
</html>
