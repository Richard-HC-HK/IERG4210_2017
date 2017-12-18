<?php
    session_start();
    include_once('lib/db.inc.php');
    global $db;
    $db = ierg4210_DB();

    //tool function of Signup
    function hash_pw($password){
        $salt = mt_rand();
        $hash = hash_hmac('sha1', $password, $salt);
        $a = array($salt,$hash);
        return $a;
    }

    function ierg4210_signup()
    {
      include_once('lib/db.inc.php');
      global $db;
      $db = ierg4210_DB();

      $user = $_POST['email'];
      $pswd = $_POST['pw'];
      $hash_pw = hash_pw($pswd);
      $flag="1";

      if (empty($user) || !preg_match('/^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$/', $user)) // if not a valid email
      {
         echo "<script>alert(\"Invalid input email, please check!\")</script>";
         echo "<script>window.location.href = \"login.php\"</script>";
          // server-side input sanitization
      }
      else
      {
      try {
          $q = $db->prepare("INSERT INTO account (email,salt,password,flag) VALUES (?,?,?,?)");

          $q->bindParam(1,$user);
          $q->bindParam(2,$hash_pw[0]);
          $q->bindParam(3,$hash_pw[1]);
          $q->bindParam(4,$flag);
          $q->execute();
          echo "<script>alert(\"Welcome! New User!\")</script>";
          echo "<script>window.location.href = \"login.php\"</script>";

      }
      catch (Exception $e){
          echo $e->getMessage();
          exit();
      }

      }
    }

    function ierg4210_changePsd()
    {
        $email = $_POST['user_email'];
        $old_pw = $_POST['old_pw'];
        $new_pw = $_POST['new_pw'];
        $r_new_pw = $_POST['r_new_pw'];


        if (empty($email) || empty($old_pw) || empty($new_pw) || empty($r_new_pw)
            || !preg_match('/^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$/', $email)
            || !preg_match('/^[A-Za-z_\d]{2,19}$/', $old_pw) || !preg_match('/^[A-Za-z_\d]{2,19}$/', $new_pw)
            || !preg_match('/^[A-Za-z_\d]{2,19}$/', $r_new_pw))
        {
            header('Location:change_psd.php', true, 302);
//            throw new Exception('Wrong Credentials');
        }
        else if ($new_pw != $r_new_pw)
        {
            header('Location:change_psd.php', true, 302);
//            throw new Exception('Two new passwords are different');
        }
        else {
            global $db;
            $db = ierg4210_DB();

            $q = $db->prepare("SELECT * FROM account WHERE email = ?");
            $q->execute(array($email));
            $r = $q->fetch();
            if (empty($r)) { // wrong email
                header('Location:change_psd.php', true, 302);
//                throw new Exception('Wrong Account!');
            }
            else { // email exists
                $salt = $r['salt'];
                $savedPwd = $r['password'];
                $sh_pwd = hash_hmac('sha1', $old_pw, $salt);
                if ($savedPwd == $sh_pwd) { //true old password
                    $new_salt = mt_rand();
                    $sh_new_pwd = hash_hmac('sha1', $new_pw, $new_salt);

                    $q = $db->prepare("UPDATE account SET password=?, salt=? WHERE email = ?");
                    $q->execute(array($sh_new_pwd, $new_salt, $email));
                    echo "<script>alert(\"Welcome! New User!\")</script>";
                    ierg4210_logout();
                }
                else {
                  echo "<script>alert(\"Wrong information provided!\")</script>";
                  echo "<script>window.location.href = \"change_psd.php\"</script>";
                }
            }
        }
    }

    function ierg4210_sendEmail() {
      $email = $_POST['Email'];

      // error_log(date("Y-m-d H:i:s"). "email:" .$email.PHP_EOL, 3, LOG_FILE);

      if (empty($email) || !preg_match('/^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$/', $email)) // if not a valid email
      {
          header('Location: login.php', true, 302);
          // error_log(date("Y-m-d H:i:s"). "Wrong Credentials" . PHP_EOL, 3, LOG_FILE);
      }
      else {
          global $db;
          $db = ierg4210_DB();

          $q = $db->prepare("SELECT * FROM account WHERE email = ?");
          $q->execute(array($email));
          $r = $q->fetch();
          if (empty($r)) { // The email doesn't exist in DB
              header('Location: findPwd.php', true, 302);
              // error_log(date("Y-m-d H:i:s"). "Wrong Email!" . PHP_EOL, 3, LOG_FILE);
          }
          else { // email exists, then send an email to it
              $to = $email;
              $subject = "Reset Password";
              $message = "Hello! There is a link for you to reset your password for Champion shop.";
              $token = mt_rand();
              // save the token(which is actually a hidden nonce)
              $q = $db->prepare("SELECT * FROM tokens WHERE email = ?");
              $q->execute(array($email));
              $r = $q->fetch();
              if (empty($r)) {
                  $q = $db->prepare("INSERT INTO tokens (email,token) VALUES (?,?)");
                  $q->execute(array($email, $token));
              }
              else {
                  $q = $db->prepare("UPDATE tokens SET token=? WHERE email = ?");
                  // error_log(date("Y-m-d H:i:s"). "hh" . PHP_EOL, 3, LOG_FILE);
                  $q->execute(array($token,$email));
                  // error_log(date("Y-m-d H:i:s"). "hh1" . PHP_EOL, 3, LOG_FILE);
              }
              $link = "https://secure.s9.ierg4210.ie.cuhk.edu.hk/phases/reset_psd.php?email=$email&token=$token";
              $message .= $link;
              $from = "hc85514301@gmail.com";
              $headers = "From: $from";
              mail($to,$subject,$message,$headers);
              echo "<script>alert(\"Reset link has been sent to your email, please check!\")</script>";
              echo "<script>window.location.href = \"login.php\"</script>";
              exit();
          }
      }
  }

    function ierg4210_login(){
        // client-side validation
        if (empty($_POST['email']) || empty($_POST['pw'])
            || !preg_match('/^[\w_]+@[\w]+(\.[\w]+){0,2}(\.[\w]{2,6})$/', $_POST['email'])
            || !preg_match('/^[A-Za-z_\d]{2,19}$/', $_POST['pw']))
            throw new Exception('Wrong e-mail or password!');

        // Implement the login logic here
        else {
            global $db;
            $db = ierg4210_DB();
            $email = $_POST['email'];
            $givenpwd = $_POST['pw'];
            $q = $db->prepare("SELECT * FROM account WHERE email = ?");
            $q->execute(array($email));
            $r = $q->fetch();
            if (empty($r))  // no such account in database
            {
                header('Location:login.php', true, 302);
                throw new Exception('Unregistered users!');
            }
            else // proceed
            {
                $salt = $r['salt'];
                $CurrentPwd = $r['password'];
                $flag = $r['flag'];        // get information from database

                $sh_pwd = hash_hmac('sha1', $givenpwd, $salt);
                if ($CurrentPwd == $sh_pwd)
                {
                    session_regenerate_id();  //prevent session fixation attack
                    $exp = time()+3600*24*3;  //3 days
                    $token = array(
                        'em'=>$email,
                        'exp'=>$exp,
                        'k'=>hash_hmac('sha1', $exp.$CurrentPwd, $salt)
                    );
                    //create cookie, make it HTTP only
                    //setcookie() must be called before printing anything out
                    setcookie('t4210',json_encode($token),$exp,'','',false,true);
                    $_SESSION['t4210'] = $token;  //put it also in the server side session

                    if ($flag == 0)
                    {
                        header('Location: admin.php', true, 302);
                        exit();
                    }
                    else
                    {
                        header('Location: index.php', true, 302);
                        exit();
                    }
                }
                else
                {
                    header('Location:login.php', true, 302);
                    throw new Exception('Wrong password!');
                }
            }
        }
      }


        function ierg4210_reset() {
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                header('Location:login.php', true, 302);
                exit;
            }
            $email = $_POST['email'];
            $token = $_POST['token'];
            $new_pw = $_POST['new_pw'];
            $r_new_pw = $_POST['r_new_pw'];

            // error_log(date("Y-m-d H:i:s"). "email:" .$email.";token:".$token.";new1:".$new_pw.";r_new:".$r_new_pw.PHP_EOL, 3, LOG_FILE);

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
                        $q->execute(array($changeToken, $email));

                        $new_salt = mt_rand();
                        $sh_new_pwd = hash_hmac('sha1', $new_pw, $new_salt);

                        $q = $db->prepare("UPDATE account SET password=?, salt=? WHERE email = ?");
                        $q->execute(array($sh_new_pwd, $new_salt, $email));
                        echo "<script>alert(\"Reset password successfully!\")</script>";
                        echo "<script>window.location.href = \"login.php\"</script>";
                        exit();
                    }
                    else // disable user to reset pswd more than once via the link
                    {
                      echo "<script>alert(\"Invalid Token! Redirect to find passwd page!\")</script>";
                      echo "<script>window.location.href = \"login.php\"</script>";
                    }
                }
            }
        }

    function ierg4210_logout(){
        // clear the cookies and session
        if (isset($_COOKIE['t4210']))
        {
            unset($_COOKIE['t4210']);
            setcookie('t4210',null, time()-3600);
            session_start();
            session_unset();
            session_destroy(); //destroy all sessions
            // redirect to login page after logout
            header('Location:login.php', true, 302);
            exit();
        }
        else
        {
            header('Location:login.php', true, 302);
            exit();
        }
    }

    header("Content-type: text/html; charset=utf-8");
    try
    {
        // input validation
        if (empty($_REQUEST['action']) || !preg_match('/^\w+$/', $_REQUEST['action']))
            throw new Exception('Undefined Action');

        // check if the form request can present a valid nonce
        include_once('lib/csrf.php');
        // csrf_verifyNonce($_REQUEST['action'], $_POST['nonce']);

        // run the corresponding function according to action
        if (($returnVal = call_user_func('ierg4210_' . $_REQUEST['action'])) === false) {
            if ($db && $db->errorCode())
                error_log(print_r($db->errorInfo(), true));
            throw new Exception('Failed');
        } else {
            // no functions are supposed to return anything
            // echo $returnVal;
        }
    }
    catch(PDOException $e) {
        error_log($e->getMessage());
        header('Refresh: 10; url=login.php?error=db');
        echo '<strong>Error Occurred:</strong> DB';
    }
    catch(Exception $e) {
        header('Refresh: 10; url=login.php?error=' . $e->getMessage());
        echo '<strong>Error Occurred:</strong> ' . $e->getMessage();
    }
?>
