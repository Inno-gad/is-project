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

// Get user data
$user = getUserData($conn, $user_id);

// Process profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'webp');
        $filename = $_FILES['profile_picture']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($ext), $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_dir = 'uploads/profile/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Update user profile picture in database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $user_id);
                
                if ($stmt->execute()) {
                    // Update session
                    $_SESSION['profile_picture'] = $new_filename;
                    $success = "Profile picture updated successfully!";
                    
                    // Update user data
                    $user = getUserData($conn, $user_id);
                } else {
                    $error = "Error updating profile picture: " . $conn->error;
                }
            } else {
                $error = "Error uploading file";
            }
        } else {
            $error = "Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.";
        }
    }
    
    // Update username and email
    if (isset($_POST['username']) && isset($_POST['email'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        
        // Check if username is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already taken";
        } else {
            // Check if email is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already taken";
            } else {
                // Update user data
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $email, $user_id);
                
                if ($stmt->execute()) {
                    // Update session
                    $_SESSION['username'] = $username;
                    $success = "Profile updated successfully!";
                    
                    // Update user data
                    $user = getUserData($conn, $user_id);
                } else {
                    $error = "Error updating profile: " . $conn->error;
                }
            }
        }
    }
    
    // If user is an artist, update artist profile
    if (isArtist() && isset($_POST['artist_name']) && isset($_POST['category'])) {
        $artist_name = $_POST['artist_name'];
        $category = $_POST['category'];
        $bio = $_POST['bio'];
        
        // Get artist data
        $artist = getArtistData($conn, $user_id);
        
        if ($artist) {
            // Update artist data
            $stmt = $conn->prepare("UPDATE artists SET artist_name = ?, category = ?, bio = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $artist_name, $category, $bio, $user_id);
        } else {
            // Insert artist data
            $stmt = $conn->prepare("INSERT INTO artists (user_id, artist_name, category, bio) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $artist_name, $category, $bio);
        }
        
        if ($stmt->execute()) {
            $success = "Artist profile updated successfully!";
        } else {
            $error = "Error updating artist profile: " . $conn->error;
        }
    }
}

// Get artist data if user is an artist
$artist = null;
if (isArtist()) {
    $artist = getArtistData($conn, $user_id);
}
?>

<div class="profile-container">
    <h1>Edit Profile</h1>
    
    <?php if (!empty($success)): ?>
        <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="profile-content">
        <div class="profile-picture-section">
            <div class="profile-picture">
                <img src="uploads/profile/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" id="profile-image-preview">
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="profile-picture-form">
                <div class="file-input-container">
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*" onchange="document.getElementById('profile-picture-form').submit();">
                    <label for="profile_picture" class="file-input-label">Change Profile Picture</label>
                </div>
            </form>
        </div>
        
        <div class="profile-details-section">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <?php if (isArtist()): ?>
                <div class="artist-profile-section">
                    <h2>Artist Profile</h2>
                    
                    <div class="form-group">
                        <label for="artist_name">Artist Name</label>
                        <input type="text" id="artist_name" name="artist_name" value="<?php echo $artist ? htmlspecialchars($artist['artist_name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="<?php echo $artist ? htmlspecialchars($artist['category']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" rows="4"><?php echo $artist ? htmlspecialchars($artist['bio']) : ''; ?></textarea>
                    </div>
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
                <?php endif; ?>
                

            </form>
        </div>
    </div>
</div>

<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .profile-container h1 {
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
    .profile-content {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }
    .profile-picture-section {
        flex: 0 0 200px;
    }
    .profile-details-section {
        flex: 1;
        min-width: 300px;
    }
    .profile-picture {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        overflow: hidden;
        margin-bottom: 15px;
    }
    .profile-picture img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .file-input-container {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }
    .file-input-container input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    .file-input-label {
        display: inline-block;
        padding: 8px 12px;
        background-color: wheat;
        color: #121212;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #fff;
    }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #333;
        background-color: #2a2a2a;
        color: #fff;
    }
    .form-group textarea {
        resize: vertical;
    }
    .save-btn {
        padding: 10px 20px;
        background-color: wheat;
        color: #121212;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    .save-btn:hover {
        background-color: #e6d9bc;
    }
    .artist-profile-section {
        margin-top: 30px;
        border-top: 1px solid #333;
        padding-top: 20px;
    }
    .artist-profile-section h2 {
        margin-bottom: 15px;
        color: wheat;
    }
</style>

<?php require_once 'includes/footer.php'; ?>