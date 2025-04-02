<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Get cart count if user is logged in
$cart_count = 0;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $cart_count = getCartCount($conn, $user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InSync - Interactive Music Platform</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div id="logo-text">
            <img src="uploads/images/insync logo.png" alt="Insync" id="logo">
        </div>

        <h1 class="logo">InSync</h1>
        <nav>
            <ul>
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="library.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'library.php' ? 'active' : ''; ?>">Your Library</a></li>
                <li><a href="playlists.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'playlists.php' ? 'active' : ''; ?>">Playlists</a></li>
                <?php if (isLoggedIn() && isArtist()): ?>
                <li><a href="artist-dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'artist-dashboard.php' ? 'active' : ''; ?>">Artist Dashboard</a></li>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                <li><a href="cart.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>">Cart
                    <?php
                    if (isLoggedIn()) {
                        $cartCount = getCartCount($conn, $_SESSION['user_id']);
                        if ($cartCount > 0) {
                            echo "<span class='cart-count'>($cartCount)</span>";
                        }
                    }
                    ?>
                </a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        <div class="nav-container">
            <div id="search-bar">
                <input type="text" id="search-input" placeholder="Search Artists, Songs, Playlists...">
            </div>
            <div class="user-menu">
                <?php if (isLoggedIn()): ?>
                    <a href="profile.php" class="profile-link">
                        <span id="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <img src="uploads/profile/<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="User" id="user-avatar">
                    </a>
                    <a href="settings.php" class="settings-link"><i class="fas fa-cog"></i></a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                    <a href="signup.php" class="signup-btn">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-area">

