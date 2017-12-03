<?php
    session_start();
    include_once('lib/db.inc.php');
    global $db;
    $db = ierg4210_DB();

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
                        header('Location: Content.php', true, 302);
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
        csrf_verifyNonce($_REQUEST['action'], $_POST['nonce']);

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
