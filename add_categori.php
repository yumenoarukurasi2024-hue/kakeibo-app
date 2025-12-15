<?php
try {
    $db = new PDO('mysql:dbname=kakeibo;host=127.0.0.1;charset=utf8', 'root', 'mysql');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . htmlspecialchars($e->getMessage());
    exit;
}

$date = $_GET['date'] ?? date('Y-m-d');
$name = $_POST['name'] ?? null;

if (empty($name)) {
    echo "カテゴリ名が入力されていません。<br>";
    echo "<a href='categori.php?date=" . htmlspecialchars($date) . "'>戻る</a>";
    exit;
}

$sql = "INSERT INTO categories (name) VALUES (?)";
$stmt = $db->prepare($sql);
$stmt->execute([$name]);

header("Location: categori.php?date=" . urlencode($date));
exit;