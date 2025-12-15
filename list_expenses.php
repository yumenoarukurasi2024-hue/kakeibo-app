<?php
require_once 'db_connect.php';

// 日付を受け取る（例: 2025-11-27）
$date = $_GET['date'] ?? date("Y-m-d");

$sql = "SELECT e.id, e.date, c.name AS category_name, e.amount, e.memo
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        WHERE e.date = ?
        ORDER BY e.date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$date]);

echo "<h3>支出一覧 (" . htmlspecialchars($date) . ")</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>日付</th><th>カテゴリ</th><th>金額</th><th>メモ</th></tr>";
foreach ($stmt as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
    echo "<td>" . (int)$row['amount'] . "円</td>";
    echo "<td>" . htmlspecialchars($row['memo']) . "</td>";
    echo "<td>
            <a href='update_expense.php?id=" . (int)$row['id'] . "'>編集</a>
            <a href='delete_expense.php?id=" . (int)$row['id'] . "'>削除</a> |
          </td>";
    echo "</tr>";
}
echo "</table>";
?>
