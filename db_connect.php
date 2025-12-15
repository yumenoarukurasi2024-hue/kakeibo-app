<?php
// DB接続
$dsn = "mysql:host=127.0.0.1;dbname=kakeibo;charset=utf8mb4";
$user = "root";
$pass = "mysql";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . htmlspecialchars($e->getMessage());
    exit;
}
?>


