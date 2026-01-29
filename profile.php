<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<div class="profile">
    <img src="uploads/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile">
    <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
    <p><?php echo htmlspecialchars($_SESSION['role']); ?></p>
</div>
