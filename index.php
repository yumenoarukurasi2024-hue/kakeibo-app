<?php
require_once 'db_connect.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

//---------現在の年月を決定-------------------
$currentYear  = isset($_GET['year'])  ? (int)$_GET['year']  : date("Y");
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date("n");


// ---------------- 月ごとの支出合計 ----------------
$sql = "SELECT SUM(amount) AS total_expense
        FROM expenses
        WHERE YEAR(date) = :year AND MONTH(date) = :month";
$stmt = $pdo->prepare($sql);
$stmt->execute([':year' => $currentYear, ':month' => $currentMonth]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$totalExpense = $row['total_expense'] ?? 0;

// ---------------- 月ごとの収入を取得（必ず最初に実行） ----------------
$sql = "SELECT amount FROM incomes WHERE user_id = :user AND year = :year AND month = :month";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':user' => $_SESSION['user_id'],
    ':year' => $currentYear,
    ':month' => $currentMonth
]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$incomeAmount = $row['amount'] ?? 0;

// ---------------- 入力された収入を保存 ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['income'])) {
    $income = (int)str_replace(',', '', $_POST['income']);

    if ($row) {
        // UPDATE
        $sql = "UPDATE incomes SET amount = :amount WHERE user_id = :user AND year = :year AND month = :month";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':amount' => $income,
            ':user' => $_SESSION['user_id'],
            ':year' => $currentYear,
            ':month' => $currentMonth
        ]);
    } else {
        // INSERT
        $sql = "INSERT INTO incomes (user_id, year, month, amount) VALUES (:user, :year, :month, :amount)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user' => $_SESSION['user_id'],
            ':year' => $currentYear,
            ':month' => $currentMonth,
            ':amount' => $income
        ]);
    }

    // 保存後に最新値を反映
    $incomeAmount = $income;
}

//---------日ごとの支出合計-------------------
$sql = "SELECT date, SUM(amount) AS total
        FROM expenses
        WHERE date >= DATE(CONCAT(:year, '-', LPAD(:month, 2, '0'), '-01'))
          AND date <  DATE_ADD(DATE(CONCAT(:year, '-', LPAD(:month, 2, '0'), '-01')), INTERVAL 1 MONTH)
        GROUP BY date
        ORDER BY date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':year' => $currentYear, ':month' => $currentMonth]);

$expenses = [];
foreach ($stmt as $row) {
    $expenses[$row['date']] = (int)$row['total'];
}

//---------------------------------------------カレンダー関数-------------------------------------------------------------------
function generateCalendar($year,$month,$expenses) {
    $firstDay = strtotime("$year-$month-01");
    $weekday = date("w", $firstDay);
    $daysInMonth = date("t", $firstDay);

    $monthId      = sprintf("%02d", $month);
    $monthDisplay = sprintf("%04d年%02d月", $year, $month); // 表示用
    $monthValue   = sprintf("%04d-%02d", $year, $month);    // 内部値

    echo "<div class='month' id='month-{$year}-{$monthId}'>";
    echo "<div class='calendar-title'>";
    echo "<button onclick='changeMonth($year," . ($month - 1) . ")'>◀ 前月</button>";
    echo "<span class='month-label'>";
    echo "<input class='monthPicker' id='monthPicker-{$year}-{$monthId}' type='text' value='{$monthDisplay}' data-value='{$monthValue}' readonly>";
    echo "</span>";
    echo "<button onclick='changeMonth($year," . ($month + 1) . ")'>次月 ▶</button>";
    echo "</div>";

    echo "<table class='calendar'>";
    echo "<tr><th class='sun'>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th class='sat'>土</th></tr><tr>";

    // ★ここを追加：月初の曜日分だけ空白セルを入れる
    for ($i = 0; $i < $weekday; $i++) {
        echo "<td></td>";
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
    $w = ($day + $weekday -1) % 7;
    $class = "";
    if ($w == 0) $class = "sun";
    if ($w == 6) $class = "sat";

    // ★高さ固定のために：calendar-cell を追加
    echo "<td class='calendar-cell $class' data-date='$dateStr'>";
    echo "<span class='day'>$day</span>";

    if (!empty($expenses[$dateStr])) {
        // ★ここを修正：expense-amount に変更
        echo "<span class='expense-amount'>" . number_format($expenses[$dateStr]) . "円</span>";
    } else {
        echo "<span class='expense-amount' style='visibility:hidden;'>0円</span>";
    }

    // 週の改行
    if ((($day + $weekday) % 7) == 0 && $day !== $daysInMonth) {
        echo "</tr><tr>";
    }
}
echo "</tr></table>";
echo "</div>";

}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>カレンダー</title>
  <link rel="stylesheet" href="style.css">

  <!-- Flatpickr本体 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <!-- 月選択プラグイン -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>

  <!-- 日本語ロケール -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
</head>
<body>

<div class="month-summary">
  <div class="kakeibo-row">
    <div class="income">
  <label> 収入</label>
  <form method="post" action="">
    <div class="income-field">
      <input type="text" class="income-input" name="income"
             value="<?= htmlspecialchars(number_format($incomeAmount)) ?>"
             inputmode="numeric" pattern="[0-9]*"
             data-year="<?= $currentYear ?>" data-month="<?= $currentMonth ?>">
      <span class="unit">円</span>
    </div>
  </form>
</div>

    <div class="expense">
      <label> 支出合計</label>
      <div class="expense-field">
        <span class="expense-total"><?= number_format($totalExpense) ?></span>
        <span class="unit">円</span>
      </div>
    </div>
  </div>
</div>


  <?php generateCalendar($currentYear, $currentMonth, $expenses); ?>
  <!-- ナビゲーション  -->
<div style="display: flex; justify-content: center;">
  <div class="nav-bar">
    <a href="screens/input.php" class="nav-item">✏ 入力</a>
    <a href="screens/expenses.php?year=<?= $currentYear ?>&month=<?= $currentMonth ?>" class="nav-item">📋 支出一覧</a>
    <a href="screens/report.php?year=<?= $currentYear ?>&month=<?= $currentMonth ?>" class="nav-item">📊 レポート</a>
  </div>
</div>
  
<!-- JavaScript -->
  <script>
    const incomeInput = document.querySelector('.income-input');
    const expenseTotal = document.querySelector('.expense-total');

    // 入力中は数字だけ
    incomeInput.addEventListener('input', () => {
      incomeInput.value = incomeInput.value.replace(/[^0-9]/g, '');
    });

    // フォーカスが外れた時にカンマ付きに変換
    incomeInput.addEventListener('blur', () => {
      let value = incomeInput.value.replace(/[^0-9]/g, '');
      if (value !== '') {
        incomeInput.value = Number(value).toLocaleString();
      }
    });

    // 支出合計を更新する関数（例）
    function updateExpenseTotal(amount) {
      expenseTotal.textContent = Number(amount).toLocaleString();
    }

    // 最小の年と月を設定（ここでは2025年1月より前は無効）
    const minYear = 2025;
    const minMonth = 1;

    // 前月・次月遷移（再読み込み）
    function changeMonth(year, month) {
      if (month < 1) { month = 12; year -= 1; }
      if (month > 12) { month = 1; year += 1; }

      if (year < minYear || (year === minYear && month < minMonth)) {
        return;
      }
      window.location.href = `?year=${year}&month=${month}`;
    }

    // 月選択ポップアップを適用（動的IDに対応）
    document.querySelectorAll("input.monthPicker").forEach((el) => {
      flatpickr(el, {
        locale: "ja",
        plugins: [new monthSelectPlugin({
          shorthand: true,
          dateFormat: "Y年m月",   // 表示形式
          altFormat: "Y-m",       // 内部値
          theme: "light"
        })],
        defaultDate: el.dataset.value, // 内部値を初期値に
        onChange: function(selectedDates, dateStr, instance) {
          const altVal = instance.formatDate(selectedDates[0], "Y-m");
          const parts = altVal.split("-");
          const year = parseInt(parts[0], 10);
          const month = parseInt(parts[1], 10);
          window.location.href = `?year=${year}&month=${month}`;
        }
      });
    });
  </script>
</body>
</html>

