<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// ログインチェック（必要なら有効化）
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 月の指定（GETパラメータがなければ現在の年月）
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");

// 開始日と終了日
$startDate = sprintf("%04d-%02d-01", $year, $month);
$endDate   = date("Y-m-d", strtotime("$startDate +1 month"));
// 支出一覧を取得
// SQLで月ごとに絞る
$sql = "SELECT e.id, e.date, c.name AS category_name, e.amount, e.memo
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        WHERE e.date >= :startDate AND e.date < :endDate
        ORDER BY e.date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':startDate' => $startDate, ':endDate' => $endDate]);
$expenses = $stmt->fetchAll();

//---------------削除処理----------------------
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM expenses WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$delete_id]);
    header("Location: index.php");
    exit;
}

//---------------編集処理----------------------
if (isset($_POST['edit_id']) && isset($_POST['new_name'])) {
    $id = intval($_POST['edit_id']);
    $newName = trim($_POST['new_name']);
    $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->execute([$newName, $id]);
}

//---------------更新処理（編集フォーム送信後）----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id          = (int)$_POST['update_id'];
    $date        = $_POST['date'];
    $category_id = $_POST['category_id'];
    $amount      = $_POST['amount'];
    $memo        = $_POST['memo'];

    $stmt = $pdo->prepare("UPDATE expenses SET date=?, category_id=?, amount=?, memo=? WHERE id=?");
    $stmt->execute([$date, $category_id, $amount, $memo, $id]);

    // POSTからyear/monthを受け取ってリダイレクト
    $year  = isset($_POST['year']) ? (int)$_POST['year'] : (int)date("Y");
    $month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date("n");

    header("Location: expenses.php?year={$year}&month={$month}");
    exit;
}

//--------------------カテゴリ一覧---------------------------------
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY id")->fetchAll();

//$filterDate = $_GET['filter_date'] ?? date("Y-m-d");
// URLパラメータから年月を受け取る（指定がなければ今日の年月）
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");

$sql = "SELECT e.id, e.date, c.name AS category_name, e.amount, e.memo
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        WHERE YEAR(e.date) = :year AND MONTH(e.date) = :month
        ORDER BY e.date ASC"; 
        // -- WHERE e.date = ?
        // -- ORDER BY e.date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['year' => $year, 'month' => $month]);//$filterDate
$expenses = $stmt->fetchAll();

//---------------編集フォーム表示----------------------
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$edit_id]);
    $expense = $stmt->fetch();

    if ($expense) {
        echo "<h2 class='expense-form-title'>支出編集</h2>";
        echo "<form action='expenses.php' method='post' class='expense-form'>";
        echo "<input type='hidden' name='update_id' value='" . (int)$expense['id'] . "'>";
        echo "<input type='hidden' name='year' value='{$year}'>";
        echo "<input type='hidden' name='month' value='{$month}'>";

        echo "<label>日付:</label>";
        echo "<input type='date' name='date' value='" . htmlspecialchars($expense['date']) . "' required>";

        echo "<label>カテゴリ:</label>";
        echo "<select name='category_id'>";
        foreach ($cats as $cat) {
            $selected = ($cat['id'] == $expense['category_id']) ? "selected" : "";
            echo "<option value='" . (int)$cat['id'] . "' $selected>" . htmlspecialchars($cat['name']) . "</option>";
        }
        echo "</select>";

        echo "<label>金額:</label>";
        echo "<input type='number' name='amount' value='" . (int)$expense['amount'] . "' required>";

        echo "<label>メモ:</label>";
        echo "<input type='text' name='memo' value='" . htmlspecialchars($expense['memo']) . "'>";
        
        echo "<div class='form-actions'>";
        echo "<button type='submit'>更新</button>";
        echo "</div>";
        echo "</form>";
        

        // ★ 支出一覧へ戻るボタンを追加
        echo "<div class='nav-bar'>";
        echo "<a href='expenses.php?year={$year}&month={$month}' class='return-button'> 支出一覧へ</a>";
        echo "</div>";

    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダー</title>
    <style>
      body {
        background: #FFE4E1; /* ← 淡い水色で統一 */
        margin: 0;
        padding: 0 15px;
        font-family: "Zen Maru Gothic", "Rounded Mplus 1c", sans-serif;
        color: #544b4b;
      }

      .nav-bar {
        display: flex;
        justify-content: center;
        margin-top: 20px;
      }
      .nav-item {
        margin: 0 15px;
        padding: 10px 20px;
        background: #a3d5ff;   /* 水色 */
        border-radius: 20px;
        text-decoration: none;
        font-size: 1.2em;
        color: blue;
        font-weight: bold;
        box-shadow: 2px 2px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
      }
      .nav-item:hover {
        background: #6ec6ff;   /* 濃い水色 */
        transform: scale(1.05);
      }

      /* 支出一覧テーブル */
      table.expense-list {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #f0f8ff;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        margin: 20px auto;
        font-family: "Zen Maru Gothic", "Rounded Mplus 1c", sans-serif;
        color: #544b4b;
      }

      /* ヘッダー */
      table.expense-list th {
        background: #f4f9ff;
        color: #6f6a6a;
        font-weight: 600;
        padding: 12px 10px;
        font-size: 0.95rem;
        border-bottom: 1px solid #eaeaea;
      }

      /* 行 */
      table.expense-list {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        margin: 20px auto;
      }
      table.expense-list tr:nth-child(even) {
        background: #e9f7ff;
      }
      table.expense-list tr:hover {
        background: #dbeeff;
      }

      /* 列ごとの装飾 */
      .exp-date { font-weight: 700; color: #4b4646; }
      .exp-category { background: #e9f7ff; border-radius: 999px; padding: 4px 10px; font-size: 0.85rem; font-weight: 600; display: inline-block; color: #3a6b8d; }
      .exp-amount { font-weight: bold; color: #ff6f91; font-size: 1.05rem; text-align: right; white-space: nowrap; }
      .exp-note { color: #8b8585; font-size: 0.9rem; }
      .exp-actions a { margin-right: 8px; color: #3a3a3a; text-decoration: none; font-weight: bold; }
      .exp-actions a:hover { color: #ff6f91; }

      /* 共通スタイル */
      .exp-actions a {
        font-family: "Zen Maru Gothic", "Rounded Mplus 1c", sans-serif;
        font-weight: bold;
        text-decoration: none;
        padding: 4px 12px;
        border-radius: 8px;
        margin-right: 8px;
        display: inline-block;
        transition: background 0.2s ease;
      }

      /* 編集ボタン（水色） */
      .exp-actions a.btn-edit {
        background: #b9e2ff;
        color: rgba(255, 255, 255, 1);
      }
      .exp-actions a.btn-edit:hover {
        background: #a3d5ff;
      }

      /* 削除ボタン（ピンク） */
      .exp-actions a.btn-delete {
        background: #ff9aa2;
        color: #fff;
      }
      .exp-actions a.btn-delete:hover {
        background: #ff6f91;
      }
      .exp-actions {
      text-align: left;   /* 操作列を右寄せにしたいなら right に */
      padding-right: 20px;
      display: flex;
      justify-content: flex-end; /* 右端に寄せる */
      gap: 8px;                  /* ボタンの間に余白 */
      }

      /* 縦線を入れる */
      .expense-list th,
      .expense-list td {
        border-right: 1px solid #e0e0e0; /* 薄いグレーの縦線 */
      }
      .expense-list th:last-child,
      .expense-list td:last-child {
        border-right: none; /* 最後の列は線なし */
      }
      /* 行ごとの下線 */
      .expense-list tr {
        border-bottom: 1px solid #eaeaea; /* 薄いグレーの線 */
      }

      .expense-list tr:last-child {
        border-bottom: none; /* 最後の行は線なし */
      }

      /* ヘッダーを少し強調 */
      .expense-list th {
        border-bottom: 2px solid #dcdcdc; /* ヘッダー下の線を濃く */
      }
      /* タイトルスタイル */
      .expense-title {
        font-family: "Rounded Mplus 1c", "Hiragino Maru Gothic ProN", sans-serif;
        font-size: 26px;
        font-weight: 600; /* boldより少し軽めで優しい */
        color: #444444; /* #333よりさらに柔らかい黒 */
        letter-spacing: 1.2px;
        padding: 6px 0;
        margin-bottom: 18px;
        text-align: center;
      }

      /* トップへ戻るボタン */
      .return-button {
        display: block;
        margin: 32px auto 0;        /* ← 中央寄せ */
        padding: 10px 24px;
        background: #a0d8ef;        /* ← 水色で統一 */
        border-radius: 16px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        color: #333;
        box-shadow: 0 2px 6px rgba(160, 216, 239, 0.3);
        transition: background 0.2s ease;
      }

      .return-button:hover {
        background: #88c9e0;        /* ← ホバー時に少し濃い水色 */
      }
      .expense-form {
        background-color: #e3f2fd;
        border-radius: 16px;
        padding: 12px 16px;
        margin-bottom: 12px;
        max-width: 500px;
        margin: 0 auto;
      }

      .expense-form-title {
        font-family: "Rounded Mplus 1c", "Hiragino Maru Gothic ProN", sans-serif;
        font-size: 24px;
        font-weight: 600;
        color: #444;
        text-align: center;
        margin-bottom: 16px;
      }

      .expense-form label {
        font-size: 16px;
        color: #444;
        margin-bottom: 4px;
        display: block;
      }

      .expense-form input,
      .expense-form select {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #a0d8ef;
        border-radius: 12px;
        font-size: 13px;
        background-color: #fffafc;
        margin-bottom: 10px;
        box-sizing: border-box;
      }

      .expense-form button {
        background-color: #a0d8ef;
        color: #333;
        font-weight: bold;
        border: none;
        border-radius: 12px;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(160, 216, 239, 0.4);
        transition: background-color 0.3s ease;
      }

      .expense-form button:hover {
        background-color: #88c9e0;
      }
      .form-actions {
      text-align: right; /* 中のボタンを右寄せ */
      }

      .return-button {
      display: block;
      margin: 32px auto 0;
      padding: 10px 24px;
      background: #a0d8ef;
      border-radius: 16px;
      font-weight: bold;
      text-align: center;
      text-decoration: none;
      color: #333;
      box-shadow: 0 2px 6px rgba(160, 216, 239, 0.3);
      transition: background 0.2s ease;
      }

      .return-button:hover {
        background: #88c9e0;
      }


  </style>
</head>
<body>
  
<?php if (!isset($_GET['edit_id'])): ?>
<h1 class="expense-title">支出一覧</h1>
<table class="expense-list">
  <tr>
    <th>日付</th><th>カテゴリ</th><th>金額</th><th>メモ</th><th>操作</th>
  </tr>
  <?php foreach ($expenses as $row): ?>
    <tr>
      <td><?= htmlspecialchars($row['date']) ?></td>
      <td><?= htmlspecialchars($row['category_name']) ?></td>
      <td><?= (int)$row['amount'] ?>円</td>
      <td><?= htmlspecialchars($row['memo']) ?></td>
      <td class="exp-actions">
        <a href="expenses.php?edit_id=<?= (int)$row['id'] ?>&year=<?= $year ?>&month=<?= $month ?>" class="btn-edit">編集</a>
        <a href="expenses.php?delete_id=<?= (int)$row['id'] ?>&year=<?= $year ?>&month=<?= $month ?>" class="btn-delete">削除</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

<!-- JavaScript -->
<script>
    //編集モード切替
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const row = this.closest('tr');
      row.querySelectorAll('.editable').forEach(span => {
        const input = document.createElement('input');
        input.type = span.dataset.field === 'amount' ? 'number' : 'text';
        input.value = span.textContent;
        input.dataset.field = span.dataset.field;
        input.dataset.id = span.dataset.id;
        span.replaceWith(input);

        input.addEventListener('blur', function() {
          const newValue = this.value.trim();
          const id = this.dataset.id;
          const field = this.dataset.field;

          fetch('update_expense.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&field=${field}&value=${encodeURIComponent(newValue)}`
          })
          .then(response => response.text())
          .then(data => {
            console.log('返答:', data);
            if (data === 'OK') {
              const newSpan = document.createElement('span');
              newSpan.className = 'editable';
              newSpan.dataset.field = field;
              newSpan.dataset.id = id;
              newSpan.textContent = newValue;
              this.replaceWith(newSpan);
            } else {
              alert('更新失敗: ' + data);
            }
          });
        });
      });
    });
  });
});
</script>

<!-- 画面の最後にトップへ戻るボタン -->
<div class="nav-bar">
  <a href="../index.php?year=<?= htmlspecialchars($_GET['year'] ?? date("Y")) ?>&month=<?= htmlspecialchars($_GET['month'] ?? date("n")) ?>" class="return-button">トップに戻る</a>
</div>

</html>
</body>
</html>