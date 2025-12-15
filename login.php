<!-- ログイン画面 -->
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログイン - 家計簿アプリ</title>
  <link rel="stylesheet" href="login_style.css"> <!-- ここで読み込む -->
</head>
<body>
  <div class="login-box">
    <h1>シンプル家計簿</h1>
    <form action="login_check.php" method="post">
      <input type="text" name="username" placeholder="ユーザーID" required>
      <input type="password" name="password" placeholder="パスワード" required>
      <button type="submit">ログイン</button>
    </form>
    <p>登録がお済みでない方は、<br>
       <a href="register.php">こちらから新規登録をお願いします。</a>
    </p>
  </div>
</body>
</html>