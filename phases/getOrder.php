<?php
    session_start();
    include_once('lib/csrf.php');
    function loggedin()
    {
        if (!empty($SESSION['t4210']))
            return $_SESSION['t4210']['em'];
        if (!empty($_COOKIE['t4210'])) {
            // stripslashes returns a string with backslashes stripped off.
            //(\' becomes ' and so on)
            if ($t = json_decode(stripslashes($_COOKIE['t4210']), true)) {
                if (time() > $t['exp']) return false;
                $db = ierg4210_DB();
                $q = $db->prepare("SELECT * FROM account WHERE email = ?");
                $q->execute(array($t['em']));
                if ($r = $q->fetch()) {
                    $realk = hash_hmac('sha1', $t['exp'] . $r['password'], $r['salt']);
                    if ($realk == $t['k']  && $r['email'] != "hc85514301@gmail.com") {
                        $_SESSION['t4210'] = $t;
                        return $t['em'];
                    }
                }
            }
        }
        return false;
    }

    include_once('lib/db.inc.php');
    global $db;
    $db = ierg4210_DB();
    $msg = json_decode($_POST["message"]);

    if (!loggedin())
    {
        $data = array(
            'ifLogin' => 0,
        );
        echo json_encode($data);
        exit();
    }

    if ($msg != null) {
        $sumPrice = 0.0;
        $order = "{";
        foreach ($msg as $pid => $number) {
            $q = $db->prepare("SELECT price FROM products WHERE pid = $pid");
            $q->execute();
            $pro_price = $q->fetchAll(PDO::FETCH_COLUMN, 0);
            $pro_price = $pro_price[0];
            settype($pro_price, "float");

            $order .= $pid . ":{" . $number . "," . $pro_price . "},";
            $sumPrice += $pro_price * $number;
        }
        $order .= "}";
        $salt = mt_rand();
        $message = "HKD;Richard@gmail.com;" . $salt . ";" . $order . ";" . $sumPrice;
        $digest = hash('md5', $message);
        $createdtime = date("Y-m-d H:i:s");

        $q = $db->prepare("INSERT INTO orders (username,digest,salt,createdtime) VALUES (?,?,?,?)");
        $q->execute(array(loggedin(),$digest, $salt, $createdtime));
        $lastInsertId = $db->lastInsertId();
        $data = array(
            'id' => $lastInsertId,
            'digest' => $digest,
        );

        $json_message = json_encode($data);
        echo json_encode($data);
        exit;
    }
?>
