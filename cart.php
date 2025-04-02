<?php
require_once 'includes/header.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items (songs)
$stmt = $conn->prepare("SELECT c.id, c.song_id, c.price, s.title, s.cover_image, a.artist_name 
                        FROM cart c 
                        JOIN songs s ON c.song_id = s.id 
                        JOIN artists a ON s.artist_id = a.id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get cart items (playlists)
$stmt = $conn->prepare("SELECT pc.id, pc.playlist_id, pc.price, p.name, p.cover_image, u.username 
                        FROM playlist_cart pc 
                        JOIN playlists p ON pc.playlist_id = p.id 
                        JOIN users u ON p.user_id = u.id 
                        WHERE pc.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_playlists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cart_songs as $item) {
    $total += $item['price'];
}
foreach ($cart_playlists as $item) {
    $total += $item['price'];
}

// Process remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $item_id = $_POST['item_id'];
    $item_type = $_POST['item_type'];

    if ($item_type === 'song') {
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    } else {
        $stmt = $conn->prepare("DELETE FROM playlist_cart WHERE id = ? AND user_id = ?");
    }

    $stmt->bind_param("ii", $item_id, $user_id);

    if ($stmt->execute()) {
        header("Location: cart.php");
        exit();
    }
}
?>

    <div class="cart-container">
        <h1>Your Cart</h1>

        <?php if (empty($cart_songs) && empty($cart_playlists)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="index.php" class="btn">Browse Music</a>
            </div>
        <?php else: ?>

            <div class="cart-items">
                <?php foreach ($cart_songs as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="uploads/covers/<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </div>
                        <div class="cart-item-info">
                            <div class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="cart-item-artist"><?php echo htmlspecialchars($item['artist_name']); ?></div>
                            <div class="cart-item-type">Song</div>
                        </div>
                        <div class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                        <div class="cart-item-actions">
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="item_type" value="song">
                                <button type="submit" name="remove" class="remove-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($cart_playlists as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="uploads/playlists/<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="cart-item-info">
                            <div class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="cart-item-artist">By <?php echo htmlspecialchars($item['username']); ?></div>
                            <div class="cart-item-type">Playlist</div>
                        </div>
                        <div class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                        <div class="cart-item-actions">
                            <form method="post">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="item_type" value="playlist">
                                <button type="submit" name="remove" class="remove-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="cart-total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <a href="checkout.php" class="checkout-btn">
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </a>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .cart-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .cart-container h1 {
            color: wheat;
            margin-bottom: 30px;
            text-align: center;
        }
        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }
        .empty-cart i {
            font-size: 48px;
            color: #aaa;
            margin-bottom: 20px;
        }
        .empty-cart p {
            font-size: 18px;
            color: #aaa;
            margin-bottom: 20px;
        }
        .empty-cart .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: wheat;
            color: #121212;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .cart-items {
            margin-bottom: 30px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            background-color: #1e1e1d;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .cart-item-image {
            width: 60px;
            height: 60px;
            margin-right: 15px;
        }
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .cart-item-artist {
            color: #aaa;
            font-size: 14px;
        }
        .cart-item-type {
            color: wheat;
            font-size: 12px;
            margin-top: 5px;
        }
        .cart-item-price {
            font-weight: bold;
            color: wheat;
            margin: 0 20px;
        }
        .cart-item-actions {
            margin-left: 10px;
        }
        .remove-btn {
            background-color: transparent;
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            font-size: 18px;
        }
        .cart-summary {
            background-color: #1e1e1d;
            border-radius: 8px;
            padding: 20px;
        }
        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #6bff6b;
            color: #121212;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }
        .checkout-btn i {
            margin-right: 10px;
        }
    </style>

<?php require_once 'includes/footer.php'; ?>