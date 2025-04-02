<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Process signup form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($user_type)) {
        $error = "Please fill in all fields";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $user_type);
                
                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;
                    
                    // If user is an artist, create artist profile
                    if ($user_type == 'artist') {
                        $stmt = $conn->prepare("INSERT INTO artists (user_id, artist_name, category) VALUES (?, ?, 'Artist')");
                        $stmt->bind_param("is", $user_id, $username);
                        $stmt->execute();
                    }
                    
                    $success = "Account created successfully! You can now <a href='login.php'>login</a>.";
                } else {
                    $error = "Error creating account: " . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - InSync</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #121212;
            padding: 20px;
        }
        .auth-form {
            background-color: #1e1e1d;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 400px;
        }
        .auth-form h1 {
            text-align: center;
            margin-bottom: 20px;
            color: wheat;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #333;
            background-color: #2a2a2a;
            color: #fff;
        }
        .auth-btn {
            width: 100%;
            padding: 10px;
            background-color: wheat;
            color: #121212;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }
        .auth-btn:hover {
            background-color: #e6d9bc;
        }
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        .auth-links a {
            color: wheat;
            text-decoration: none;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #ff6b6b;
            margin-bottom: 15px;
            text-align: center;
        }
        .success-message {
            color: #6bff6b;
            margin-bottom: 15px;
            text-align: center;
        }
        .user-type-container {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            background-color: #2a2a2a;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .user-type-option:hover {
            background-color: #3a3a3a;
        }
        .user-type-option.selected {
            background-color: #4a4a4a;
            border: 2px solid wheat;
        }
        .user-type-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h1>Sign Up for InSync</h1>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php else: ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label>I am joining as:</label>
                    <div class="user-type-container">
                        <div class="user-type-option" data-value="fan">
                            <div class="user-type-icon"><i class="fas fa-headphones"></i></div>
                            <div>Fan</div>
                        </div>
                        <div class="user-type-option" data-value="artist">
                            <div class="user-type-icon"><i class="fas fa-music"></i></div>
                            <div>Artist</div>
                        </div>
                    </div>
                    <input type="hidden" id="user_type" name="user_type" value="fan">
                </div>
                
                <button type="submit" class="auth-btn">Sign Up</button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // User type selection
        const userTypeOptions = document.querySelectorAll('.user-type-option');
        const userTypeInput = document.getElementById('user_type');
        
        userTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                userTypeOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                userTypeInput.value = this.dataset.value;
            });
        });
        
        // Set default selection
        userTypeOptions[0].classList.add('selected');
    </script>
</body>
</html>