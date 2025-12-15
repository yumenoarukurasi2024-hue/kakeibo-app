<?php
try {
    $db = new PDO(
        'mysql:dbname=kakeibo;host=127.0.0.1;charset=utf8',
        'root','mysql');
    
}catch (PDOException $e) {
        echo'DB接続エラー' . $e->getMessage();
}
    $sql = "SELECT id, date, category, amount, memo FROM expenses";
    foreach ($db->query($sql) as $row) {
        echo $row['id']." | ".
             $row['date']." | ".
             $row['category']." | ".
             $row['amount']."円 | ".
             $row['memo']."<br>";
    }
?>