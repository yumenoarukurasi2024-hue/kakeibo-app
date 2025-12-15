<!-- 新規登録画面 -->
<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // パスワードをハッシュ化して保存
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashed]);

        // 登録後はログイン画面へリダイレクト
        header("Location: login.php");
        exit;
    } else {
        echo "入力が不足しています";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>新規登録</title>
  <link rel="stylesheet" href="login_style.css"> <!-- ログインと同じCSSを使う -->
</head>
<body>
  <div class="login-box">
    <h1>新規登録</h1>
    <form action="register.php" method="post">
      <input type="text" name="username" placeholder="ユーザーID" required>
      <input type="password" name="password" placeholder="パスワード" required>
      <button type="submit">登録</button>
    </form>
    <p><a href="login.php">ログイン画面へ戻る</a></p>
  </div>
</body>
</html>
