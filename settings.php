<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get billing info
$stmt = $conn->prepare("SELECT * FROM billing_info WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$billing = $result->fetch_assoc();

// Process billing info update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $country = $_POST['country'];
    $postal_code = $_POST['postal_code'];
    $card_number = $_POST['card_number'];
    $card_expiry = $_POST['card_expiry'];
    
    // Validate card number (basic validation)
    $card_number = preg_replace('/\s+/', '', $card_number);
    if (!preg_match('/^\d{16}$/', $card_number)) {
        $error = "Invalid card number. Please enter a 16-digit number.";
    } 
    // Validate expiry date (MM/YY format)
    elseif (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $card_expiry)) {
        $error = "Invalid expiry date. Please use MM/YY format.";
    } else {
        // Encrypt card number (in a real app, use a more secure method)
        $encrypted_card = password_hash($card_number, PASSWORD_DEFAULT);
        
        if ($billing) {
            // Update billing info
            $stmt = $conn->prepare("UPDATE billing_info SET full_name = ?, address = ?, city = ?, country = ?, postal_code = ?, card_number = ?, card_expiry = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $full_name, $address, $city, $country, $postal_code, $encrypted_card, $card_expiry, $user_id);
        } else {
            // Insert billing info
            $stmt = $conn->prepare("INSERT INTO billing_info (user_id, full_name, address, city, country, postal_code, card_number, card_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $user_id, $full_name, $address, $city, $country, $postal_code, $encrypted_card, $card_expiry);
        }
        
        if ($stmt->execute()) {
            $success = "Billing information updated successfully!";
            
            // Refresh billing info
            $stmt = $conn->prepare("SELECT * FROM billing_info WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $billing = $result->fetch_assoc();
        } else {
            $error = "Error updating billing information: " . $conn->error;
        }
    }
}

// Check if redirected from checkout
$from_checkout = isset($_GET['checkout']) && $_GET['checkout'] == 1;
?>

<div class="settings-container">
    <h1>Settings</h1>
    
    <?php if (!empty($success)): ?>
        <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($from_checkout): ?>
        <div class="checkout-message">
            <p>Please update your billing information to complete your purchase.</p>
        </div>
    <?php endif; ?>
    
    <div class="settings-tabs">
        <div class="tab active" data-tab="billing">Billing Information</div>
        <div class="tab" data-tab="account">Account Settings</div>
    </div>
    
    <div class="settings-content">
        <div class="tab-pane active" id="billing-tab">
            <h2>Billing Information</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo $billing ? htmlspecialchars($billing['full_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo $billing ? htmlspecialchars($billing['address']) : ''; ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo $billing ? htmlspecialchars($billing['city']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" value="<?php echo $billing ? htmlspecialchars($billing['country']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" value="<?php echo $billing ? htmlspecialchars($billing['postal_code']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="payment-section">
                    <h3>Payment Information</h3>
                    
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="card_expiry">Expiry Date (MM/YY)</label>
                            <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" value="<?php echo $billing ? htmlspecialchars($billing['card_expiry']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="card_cvv">CVV</label>
                            <input type="text" id="card_cvv" name="card_cvv" placeholder="123" required>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="save-btn">Save Billing Information</button>
                
                <?php if ($from_checkout): ?>
                    <a href="cart.php" class="back-to-cart-btn">Back to Cart</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="tab-pane" id="account-tab">
            <h2>Account Settings</h2>
            
            <div class="account-options">
                <a href="profile.php" class="account-option">
                    <i class="fas fa-user"></i>
                    <div>
                        <h3>Edit Profile</h3>
                        <p>Update your profile information and picture</p>
                    </div>
                </a>
                
                <a href="#" class="account-option" id="change-password-btn">
                    <i class="fas fa-lock"></i>
                    <div>
                        <h3>Change Password</h3>
                        <p>Update your password</p>
                    </div>
                </a>
                
                <a href="logout.php" class="account-option">
                    <i class="fas fa-sign-out-alt"></i>
                    <div>
                        <h3>Logout</h3>
                        <p>Sign out of your account</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .settings-container h1 {
        margin-bottom: 20px;
        color: wheat;
    }
    .success-message {
        background-color: rgba(107, 255, 107, 0.2);
        color: #6bff6b;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .error-message {
        background-color: rgba(255, 107, 107, 0.2);
        color: #ff6b6b;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .checkout-message {
        background-color: rgba(255, 193, 7, 0.2);
        color: #ffc107;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .settings-tabs {
        display: flex;
        border-bottom: 1px solid #333;
        margin-bottom: 20px;
    }
    .tab {
        padding: 10px 20px;
        cursor: pointer;
        color: #aaa;
    }
    .tab.active {
        color: wheat;
        border-bottom: 2px solid wheat;
    }
    .tab-pane {
        display: none;
    }
    .tab-pane.active {
        display: block;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #fff;
    }
    .form-group input {
        width: 100%;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #333;
        background-color: #2a2a2a;
        color: #fff;
    }
    .form-row {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    .form-row .form-group {
        flex: 1;
        min-width: 200px;
    }
    .payment-section {
        margin-top: 20px;
        border-top: 1px solid #333;
        padding-top: 20px;
    }
    .payment-section h3 {
        margin-bottom: 15px;
        color: wheat;
    }
    .save-btn {
        padding: 10px 20px;
        background-color: wheat;
        color: #121212;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 20px;
    }
    .save-btn:hover {
        background-color: #e6d9bc;
    }
    .back-to-cart-btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: transparent;
        color: wheat;
        border: 1px solid wheat;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 20px;
        margin-left: 10px;
        text-decoration: none;
    }
    .back-to-cart-btn:hover {
        background-color: rgba(245, 222, 179, 0.1);
    }
    .account-options {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .account-option {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background-color: #2a2a2a;
        border-radius: 4px;
        text-decoration: none;
        color: #fff;
        transition: background-color 0.3s;
    }
    .account-option:hover {
        background-color: #3a3a3a;
    }
    .account-option i {
        font-size: 24px;
        color: wheat;
    }
    .account-option h3 {
        margin-bottom: 5px;
        color: wheat;
    }
    .account-option p {
        color: #aaa;
        font-size: 14px;
    }
</style>

<script>
    // Tab switching
    const tabs = document.querySelectorAll('.tab');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs and panes
            tabs.forEach(t => t.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            document.getElementById(`${this.dataset.tab}-tab`).classList.add('active');
        });
    });
    
    // Format card number input
    const cardNumberInput = document.getElementById('card_number');
    cardNumberInput.addEventListener('input', function(e) {
        // Remove all non-digits
        let value = this.value.replace(/\D/g, '');
        
        // Add a space after every 4 digits
        if (value.length > 0) {
            value = value.match(/.{1,4}/g).join(' ');
        }
        
        // Update the input value
        this.value = value;
    });
    
    // Format expiry date input
    const expiryInput = document.getElementById('card_expiry');
    expiryInput.addEventListener('input', function(e) {
        // Remove all non-digits
        let value = this.value.replace(/\D/g, '');
        
        // Add a slash after the first 2 digits
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        // Update the input value
        this.value = value;
    });
</script>

<?php require_once 'includes/footer.php'; ?>