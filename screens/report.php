<?php
// DB接続
try {
    $db = new PDO('mysql:dbname=kakeibo;host=127.0.0.1;charset=utf8', 'root', 'mysql');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . htmlspecialchars($e->getMessage());
    exit;
}

// 年月を受け取る（指定がなければ今日の年月）
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");

// SQLで使う "YYYY-MM" 形式に整形
$target_month = sprintf("%04d-%02d", $year, $month);

//カテゴリ別集計
$sql = "SELECT c.name AS category_name, COUNT(*) AS usage_count
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        WHERE DATE_FORMAT(e.date, '%Y-%m') = ?
        GROUP BY c.id
        ORDER BY usage_count DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$target_month]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($target_month); ?> カテゴリ別支出円グラフ</title>
  <link rel="stylesheet" href="../style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <h2><?php echo htmlspecialchars($target_month); ?> カテゴリ別支出</h2>
  <canvas id="categoryChart"></canvas>
  <script>
  const ctx = document.getElementById('categoryChart').getContext('2d');
  const chart = new Chart(ctx, {
      type: 'pie',
      data: {
          labels: <?php echo json_encode(array_column($data, 'category_name')); ?>,
          datasets: [{
              data: <?php echo json_encode(array_column($data, 'usage_count')); ?>,
              backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40']
          }]
      }
  });
  </script>

  <!-- 画面の最後にトップへ戻るボタン -->
<div class="nav-bar">
  <a href="../index.php?year=<?= $year ?>&month=<?= $month ?>" class="nav-item"> トップに戻る</a>
</div>
</body>
</html>