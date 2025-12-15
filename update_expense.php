<?php
$pdo = new PDO('mysql:host=localhost;dbname=your_db;charset=utf8','user','pass');

if (isset($_POST['id'], $_POST['field'], $_POST['value'])) {
    $id = intval($_POST['id']);
    $field = $_POST['field'];
    $value = trim($_POST['value']);
    $stmt = $pdo->prepare("UPDATE expenses SET $field = ? WHERE id = ?");
    if ($stmt->execute([$value, $id])) {
        echo "OK";
    } else {
        echo "ERROR";
    }
}
?>
