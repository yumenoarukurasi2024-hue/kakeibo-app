<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    exit("ログインしてください");
}

$user_id = $_SESSION['user_id'];
$year    = (int)$_POST['year'];
$month   = (int)$_POST['month'];
$amount  = (int)$_POST['amount'];

// 既存データ確認
$sql = "SELECT id FROM incomes WHERE user_id = :user AND year = :year AND month = :month";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user' => $user_id, ':year' => $year, ':month' => $month]);
$row = $stmt->fetch();

if ($row) {
    // UPDATE
    $sql = "UPDATE incomes SET amount = :amount WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':amount' => $amount, ':id' => $row['id']]);
    echo "更新しました";
} else {
    // INSERT
    $sql = "INSERT INTO incomes (user_id, year, month, amount) VALUES (:user, :year, :month, :amount)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user' => $user_id, ':year' => $year, ':month' => $month, ':amount' => $amount]);
    echo "保存しました";
}
