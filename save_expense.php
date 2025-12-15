<?php
// DB接続
try {
    $db = new PDO('mysql:dbname=kakeibo;host=127.0.0.1;charset=utf8', 'root', 'mysql');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . htmlspecialchars($e->getMessage());
    exit;
}

$date = $_POST['date'] ?? ($_GET['date'] ?? date('Y-m-d'));
$amount = $_POST['amount'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$memo = $_POST['memo'] ?? null;

// 入力チェック
if (empty($amount) || empty($category_id)) {
    echo "金額またはカテゴリが入力されていません。<br>";
    echo "<a href='categori.php?date=" . htmlspecialchars($date) . "'>戻る</a>";
    exit;
}

// 保存処理
$sql = "INSERT INTO expenses (date, amount, category_id, memo) VALUES (?, ?, ?, ?)";
$stmt = $db->prepare($sql);
$stmt->execute([$date, $amount, $category_id, $memo]);

// 完了後に categori.php に戻す
header("Location: categori.php?date=" . urlencode($date));
exit;

