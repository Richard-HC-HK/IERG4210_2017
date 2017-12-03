<?php
    include_once('db.inc.php');
    global $db;
    $db = ierg4210_DB();
    $num=1;
    $q = $db->prepare("DELETE FROM account where flag = ?");
    $q->bindParam(1,$num);
    $q->execute();
    ?>
