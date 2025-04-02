<?php
require_once 'includes/header.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items (songs)
$stmt = $conn->prepare("SELECT c.id, c.song_id, c.price, s.title 
                        FROM cart c 
                        JOIN songs s ON c.song_id = s.id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get cart items (playlists)
$stmt = $conn->prepare("SELECT pc.id, pc.playlist_id, pc.price, p.name 
                        FROM playlist_cart pc 
                        JOIN playlists p ON pc.playlist_id = p.id 
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

// Process checkout
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Validate payment information
    $card_number = $_POST['card_number'] ?? '';
    $card_name = $_POST['card_name'] ?? '';
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    if (empty($card_number) || empty($card_name) || empty($expiry) || empty($cvv)) {
        $error_message = 'Please fill in all payment details';
    } else {
        // In a real app, you would process payment here
        
        // Move songs from cart to purchases
        foreach ($cart_songs as $item) {
            $stmt = $conn->prepare("INSERT INTO purchases (user_id, song_id, price, purchase_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iid", $user_id, $item['song_id'], $item['price']);
            $stmt->execute();
        }
        
        // Move playlists from cart to purchases
        foreach ($cart_playlists as $item) {
            $stmt = $conn->prepare("INSERT INTO playlist_purchases (user_id, playlist_id, price, purchase_date) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iid", $user_id, $item['playlist_id'], $item['price']);
            $stmt->execute();
        }
        
        // Clear cart
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM playlist_cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $success_message = 'Payment successful! Your purchases are now available in your library.';
    }
}

// Redirect to library if cart is empty
if (empty($cart_songs) && empty($cart_playlists) && empty($success_message)) {
    header("Location: cart.php");
    exit();
}
?>

<div class="checkout-container">
    <h1>Checkout</h1>
    
    <?php if (!empty($success_message)): ?>
    <div class="success-message">
        <i class="fas fa-check-circle"></i>
        <p><?php echo $success_message; ?></p>
        <a href="library.php" class="btn">Go to Library</a>
    </div>
    <?php else: ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <p><?php echo $error_message; ?></p>
    </div>
    <?php endif; ?>
    
    <div class="checkout-grid">
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="order-items">
                <?php foreach ($cart_songs as $item): ?>
                <div class="order-item">
                    <div class="order-item-name"><?php echo htmlspecialchars($item['title']); ?></div>
                    <div class="order-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($cart_playlists as $item): ?>
                <div class="order-item">
                    <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?> (Playlist)</div>
                    <div class="order-item-price">$<?php echo number_format($item['price'], 2); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-total">
                <span>Total:</span>
                <span>$<?php echo number_format($total, 2); ?></span>
            </div>
        </div>
        
        <div class="payment-form">
            <h2>Payment Information</h2>
            <form method="post">
                <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                </div>
                
                <div class="form-group">
                    <label for="card_name">Name on Card</label>
                    <input type="text" id="card_name" name="card_name" placeholder="John Doe" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" required>
                    </div>
                </div>
                
                <button type="submit" name="checkout" class="checkout-btn">
                    <i class="fas fa-lock"></i> Pay $<?php echo number_format($total, 2); ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .checkout-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .checkout-container h1 {
        color: wheat;
        margin-bottom: 30px;
        text-align: center;
    }
    .success-message {
        text-align: center;
        padding: 50px 0;
        background-color: #1e1e1d;
        border-radius: 8px;
    }
    .success-message i {
        font-size: 48px;
        color: #6bff6b;
        margin-bottom: 20px;
    }
    .success-message p {
        font-size: 18px;
        margin-bottom: 20px;
    }
    .success-message .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: wheat;
        color: #121212;
        border-radius: 20px;
        text-decoration: none;
        font-weight: bold;
    }
    .error-message {
        background-color: rgba(255, 107, 107, 0.2);
        border-left: 4px solid #ff6b6b;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    .error-message i {
        color: #ff6b6b;
        font-size: 20px;
        margin-right: 10px;
    }
    .checkout-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    .order-summary, .payment-form {
        background-color: #1e1e1d;
        border-radius: 8px;
        padding: 20px;
    }
    .order-summary h2, .payment-form h2 {
        color: wheat;
        margin-bottom: 20px;
        font-size: 20px;
    }
    .order-items {
        margin-bottom: 20px;
    }
    .order-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #333;
    }
    .order-item:last-child {
        border-bottom: none;
    }
    .order-item-name {
        flex: 1;
    }
    .order-item-price {
        font-weight: bold;
    }
    .order-total {
        display: flex;
        justify-content: space-between;
        font-size: 18px;
        font-weight: bold;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #333;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-row {
        display: flex;
        gap: 20px;
    }
    .form-row .form-group {
        flex: 1;
    }
    label {
        display: block;
        margin-bottom: 5px;
        color: #aaa;
    }
    input[type="text"] {
        width: 100%;
        padding: 10px;
        background-color: #2a2a2a;
        border: 1px solid #333;
        border-radius: 4px;
        color: white;
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
        cursor: pointer;
    }
    .checkout-btn i {
        margin-right: 10px;
    }
    
    @media (max-width: 768px) {
        .checkout-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>