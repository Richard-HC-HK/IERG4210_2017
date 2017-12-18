<?php
    include_once('lib/csrf.php');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Change Password</title>
</head>
<body>
<h1>Change Password</h1>
<fieldset>
    <legend>Forms</legend>
    <form method="POST" action="auth-process.php?action=<?php echo ($action = 'changePsd'); ?>">
        <label for="user_email">Input Email:</label>
        <input type="email" name="user_email" required="true" pattern="^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$" />
        <p></p>
        <label for="old_pw">Old Password:</label>
        <input type="password" name="old_pw" required="true" pattern="^[A-Za-z_\d]\w{2,19}$" />
        <p></p>
        <label for="new_pw">New Password:</label>
        <input type="password" name="new_pw" required="true" pattern="^[A-Za-z_\d]\w{2,19}$" />
        <p></p>
        <label for="r_new_pw">Repeat New_psd:</label>
        <input type="password" name="r_new_pw" required="true" pattern="^[A-Za-z_\d]\w{2,19}$" />

        <input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
        <input type="submit" value="Confirm" />
    </form>
</fieldset>

<p><a href="index.php">Cancel</a></p>
</body>
</html>
