<?php
// DB接続
try {
    $db = new PDO('mysql:dbname=kakeibo;host=127.0.0.1;charset=utf8', 'root', 'mysql');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . $e->getMessage();
    exit;
}

// フォームから送られた値を受け取る
$date      = $_POST['date'] ?? null;
$amount    = $_POST['amount'] ?? null;
$category  = $_POST['category_id'] ?? null;
$memo      = $_POST['memo'] ?? null;

// 入力チェック
if (empty($date) || empty($amount) || empty($category)) {
    echo "必要な項目が入力されていません。<br>";
    echo "<a href='categori.php?date=" . htmlspecialchars($date) . "'>戻る</a>";
    exit;
}

// 保存処理
$sql = "INSERT INTO expenses (date, amount, category_id, memo) VALUES (?, ?, ?, ?)";
$stmt = $db->prepare($sql);
$stmt->execute([$date, $amount, $category, $memo]);

// 完了メッセージ
echo "保存しました！<br>";
echo "<a href='index.php'>カレンダーに戻る</a>";
