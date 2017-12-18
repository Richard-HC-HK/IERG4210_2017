<?php
    include_once('db.inc.php');
    global $db;
    $db = ierg4210_DB();

    //Create salted & hashed password
    function hash_pw($password){
        $salt = mt_rand();
        $hash = hash_hmac('sha1', $password, $salt);
        $a = array($salt,$hash);
        return $a;
    }
    $admin = "hc85514301@gmail.com";
    $adminPwd = "hc85514301";
    $hash_pw0 = hash_pw($adminPwd);
    $flag0="0";

    $user1 = "Richard@gmail.com";
    $pswd1 = "Richard";
    $hash_pw1 = hash_pw($pswd1);
    $flag1="1";

    $user2 = "Sherry@gmail.com";
    $pswd2 = "Sherry";
    $hash_pw2 = hash_pw($pswd2);
    $flag2="1";

    try {
        $q = $db->prepare("INSERT INTO account (email,salt,password,flag) VALUES (?,?,?,?)");
        $q->bindParam(1,$admin);
        $q->bindParam(2,$hash_pw0[0]);
        $q->bindParam(3,$hash_pw0[1]);
        $q->bindParam(4,$flag0);
        $q->execute();

        $q->bindParam(1,$user1);
        $q->bindParam(2,$hash_pw1[0]);
        $q->bindParam(3,$hash_pw1[1]);
        $q->bindParam(4,$flag1);
        $q->execute();

        $q->bindParam(1,$user2);
        $q->bindParam(2,$hash_pw2[0]);
        $q->bindParam(3,$hash_pw2[1]);
        $q->bindParam(4,$flag1);
        $q->execute();
//        echo "<p>Generated successfully</p>";
    }
    catch (Exception $e){
        echo $e->getMessage();
        exit();
    }
?>
