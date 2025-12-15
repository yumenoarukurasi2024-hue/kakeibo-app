<?php
// DB接続
try {
    $db = new PDO('mysql:dbname=kakeibo;host=127.0.0.1;charset=utf8', 'root', 'mysql');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . $e->getMessage();
    exit;
}

// URLから日付を取得
$date = $_GET['date'] ?? date('Y-m-d');

// カテゴリ一覧を取得
$categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// この日付の支出一覧を取得
$sql = "SELECT e.amount, e.memo, c.name AS category_name
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        WHERE e.date = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$date]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2><?php echo htmlspecialchars($date); ?> の支出入力</h2>

<!-- 支出入力フォーム -->
<form method="post" action="save_expense.php?date=<?php echo htmlspecialchars($date); ?>">

    <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">

    金額: <input type="number" name="amount" required><br>
    カテゴリ:<select name="category_id">    
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo $category['id']; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </option>
        <?php endforeach; ?>
    </select><br>

    メモ: <input type="text" name="memo"><br>
    <button type="submit">保存</button>
</form>

<hr>
<!-- カテゴリ追加フォーム -->
<form method="post" action="add_categori.php?date=<?php echo htmlspecialchars($date); ?>">
    新しいカテゴリ: <input type="text" name="name" required>
    <button type="submit">追加</button>
</form>

<hr>

<!-- この日の支出一覧 -->
<h3>この日の支出一覧</h3>
<?php if (!empty($expenses)): ?>
    <ul>
        <?php foreach ($expenses as $expense): ?>
            <li>
                <?php echo number_format($expense['amount']); ?>円 -
                <?php echo htmlspecialchars($expense['category_name']); ?> -
                <?php echo htmlspecialchars($expense['memo']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>まだ支出は登録されていません。</p>
<?php endif; ?>
