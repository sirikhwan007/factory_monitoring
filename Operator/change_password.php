<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operator') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

if (empty($password) || $password !== $confirm) {
    header("Location: profile.php");
    exit();
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
$stmt->bind_param("ss", $hashed, $user_id);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: profile.php");
exit();
