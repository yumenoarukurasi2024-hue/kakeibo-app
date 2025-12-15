<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// ログインチェック（必要なら有効化）
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 年月を受け取る（指定がなければ今日の年月）
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");

// カテゴリ一覧取得
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY id")->fetchAll();

// ------------------------支出登録処理-------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'])) {
    $date        = $_POST['date'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $amount      = $_POST['amount'] ?? null;
    $memo        = $_POST['memo'] ?? null;

    $sql = "INSERT INTO expenses (date, category_id, amount, memo) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date, $category_id, $amount, $memo]); 

    // POSTからyear/monthを受け取ってリダイレクト
    $year  = isset($_POST['year']) ? (int)$_POST['year'] : (int)date("Y");
    $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date("n");

    // 入力日付から年月を算出
    $ts    = strtotime($date);
    $year  = (int)date('Y', $ts);
    $month = (int)date('n', $ts);

    // 成功したら指定月のトップへ戻る
    header("Location: input.php?year={$year}&month={$month}");
    exit;
}

//-----------------------カテゴリ追加処理----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category'])) {
    $newCategory = $_POST['new_category'] ?? null;
    if ($newCategory) {
        $sql = "INSERT INTO categories (name) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newCategory]);
    }
    // 成功したら指定付きのトップへ戻る
    header("Location: ../index.php?year={$year}&month={$month}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支出入力</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
<h1 class="expense-title">支出入力画面 ✏</h1>

<!-- 入力フォーム -->
<h2 class="section-title">支出登録</h2>
<form action="input.php" method="post" class="expense-form">
  <input type="hidden" name="year" value="<?= $year ?>">
  <input type="hidden" name="month" value="<?= $month ?>">
  
  <label>日付: <input type="date" name="date" required></label><br>
  <label>カテゴリ:
    <select name="category_id" required>
      <?php foreach ($cats as $cat): ?>
        <option value="<?= htmlspecialchars($cat['id']) ?>">
          <?= htmlspecialchars($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label><br>
  <label>金額: <input type="number" name="amount" min="0" step="1" required></label><br>
  <label>メモ: <input type="text" name="memo"></label><br>
  
  <!-- ボタンだけ右寄せ -->
  <div class="button-right">
    <button type="submit" class="primary-btn">登録</button>
  </div>
</form>

<!-- カテゴリ追加フォーム -->
<h2 class="section-title">カテゴリ追加</h2>
<form action="input.php" method="post" class="expense-form">
  <label>新しいカテゴリ名: <input type="text" name="new_category" required></label>
  
  <!-- ボタンだけ右寄せ -->
  <div class="button-right">
    <button type="submit" class="secondary-btn">追加</button>
  </div>
</form>


<!-- 画面の最後にトップへ戻るボタン -->
<div class="nav-bar">
  <a href="../index.php?year=<?= htmlspecialchars($year, ENT_QUOTES) ?>&month=<?= htmlspecialchars($month, ENT_QUOTES) ?>" class="nav-item">トップに戻る</a>
</div>

</body>
</html>
