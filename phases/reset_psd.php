<?php
    include_once('lib/csrf.php');
    define("LOG_FILE", "/var/www/ipn.log");
    include_once('lib/db.inc.php');
    global $db;
    $db = ierg4210_DB();

    function ierg4210_reset() {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            header('Location:login.php', true, 302);
            exit;
        }
        $email = $_POST['email'];
        $token = $_POST['token'];
        $new_pw = $_POST['new_pw'];
        $r_new_pw = $_POST['r_new_pw'];

        error_log(date("Y-m-d H:i:s"). "email:" .$email.";token:".$token.";new1:".$new_pw.";r_new:".$r_new_pw.PHP_EOL, 3, LOG_FILE);

        if (empty($email) || empty($new_pw) || empty($r_new_pw)
            || !preg_match('/^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$/', $email)
            || !preg_match('/^[A-Za-z_\d]{2,19}$/', $new_pw)
            || !preg_match('/^[A-Za-z_\d]{2,19}$/', $r_new_pw))
        {
            echo "<script>alert(\"Wrong email/password format!\")</script>";
            // error_log(date("Y-m-d H:i:s"). "Wrong email/password format!" . PHP_EOL, 3, LOG_FILE);
        }
        else if ($new_pw != $r_new_pw)
        {
            echo "<script>alert(\"Two new passwords are different!\")</script>";
            // error_log(date("Y-m-d H:i:s"). "Two new passwords are different" . PHP_EOL, 3, LOG_FILE);
        }
        else {
            global $db;
            $db = ierg4210_DB();

            // validate the email and token
            $q = $db->prepare("SELECT * FROM tokens WHERE email = ?");
            $q->execute(array($email));
            $r = $q->fetch();
            if (empty($r)) {
                echo "<script>alert(\"The email isn't for an existed user!\")</script>";
            }
            else { // email exists
                $saved_token = $r['token'];
                if ($saved_token == $token) { //true token, clear the email&token in tokens, reset psd
                    error_log(date("Y-m-d H:i:s"). "email exists" . PHP_EOL, 3, LOG_FILE);

                    $changeToken = mt_rand();
                    $q = $db->prepare("UPDATE tokens SET token=? WHERE email = ?");
                    $q->execute(array($changeToken, $email));  // set another random token to disable the original link

                    $new_salt = mt_rand();
                    $sh_new_pwd = hash_hmac('sha1', $new_pw, $new_salt);

                    $q = $db->prepare("UPDATE account SET password=?, salt=? WHERE email = ?");
                    $q->execute(array($sh_new_pwd, $new_salt, $email));
                    echo "<script>alert(\"Reset password successfully!\")</script>";
                    echo "<script>window.location.href = \"login.php\"</script>";
                    exit;
                }
                else // disable user to reset pswd more than once via the link
                {
                  echo '<script language="javascript">';
                  echo 'alert("Invalid Token!")';
                  echo '</script>';
                }
            }
        }
    }
    header("Content-type: text/html; charset=utf-8");
    // input validation
    if (!empty($_REQUEST['action']) && !empty($_REQUEST['email']) && !empty($_REQUEST['token'])) {
        if (!preg_match('/^\w+$/', $_REQUEST['action']))
            error_log(date("Y-m-d H:i:s"). "Undefined Action" . PHP_EOL, 3, LOG_FILE);
            // check if the form request can present a valid nonce
        include_once('lib/csrf.php');
        csrf_verifyNonce($_REQUEST['action'], $_POST['nonce']);
    }

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Password</h1>
<fieldset>
    <form method="POST" action="auth-process.php?action=<?php echo ($action = 'reset');?>">
        <label for="new_pw">New Password:</label>
        <input type="password" name="new_pw" required="true" pattern="^[A-Za-z_\d]\w{2,19}$" />
        <p></p>
        <label for="r_new_pw">Repeat New_psd:</label>
        <input type="password" name="r_new_pw" required="true" pattern="^[A-Za-z_\d]\w{2,19}$" />

        <input type="hidden" name="email" value="<?php parse_str($_SERVER['QUERY_STRING']); echo $email?>" />
        <input type="hidden" name="token" value="<?php parse_str($_SERVER['QUERY_STRING']); echo $token?>" />
        <input type="hidden" name="nonce" value="<?php echo csrf_getNonce($action); ?>" />
        <input type="submit" value="confirm" />
    </form>
</fieldset>

<p>
    <a href="index.php">Cancel</a>&emsp;
    <a href="login.php">Login</a>
</p>

</body>
</html>
