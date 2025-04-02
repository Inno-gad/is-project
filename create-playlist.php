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

// Process playlist creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    
    // Validate input
    if (empty($name)) {
        $error = "Please enter a playlist name";
    } else {
        // Handle cover image upload
        $cover_image = 'placeholder.jpg'; // Default cover
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
            $allowed = array('jpg', 'jpeg', 'png', 'webp');
            $filename = $_FILES['cover_image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/playlists/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    $cover_image = $new_filename;
                } else {
                    $error = "Error uploading file";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.";
            }
        }
        
        if (empty($error)) {
            // Insert playlist
            $stmt = $conn->prepare("INSERT INTO playlists (name, user_id, cover_image) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $name, $user_id, $cover_image);
            
            if ($stmt->execute()) {
                $playlist_id = $stmt->insert_id;
                
                // Add selected songs to playlist
                if (isset($_POST['songs']) && is_array($_POST['songs'])) {
                    $position = 1;
                    
                    foreach ($_POST['songs'] as $song_id) {
                        $stmt = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id, position) VALUES (?, ?, ?)");
                        $stmt->bind_param("iii", $playlist_id, $song_id, $position);
                        $stmt->execute();
                        $position++;
                    }
                }
                
                $success = "Playlist created successfully!";
            } else {
                $error = "Error creating playlist: " . $conn->error;
            }
        }
    }
}

// Get available songs
$stmt = $conn->prepare("SELECT s.id, s.title, a.artist_name, s.cover_image 
                        FROM songs s 
                        JOIN artists a ON s.artist_id = a.id 
                        ORDER BY s.title");
$stmt->execute();
$songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="create-playlist-container">
    <h1>Create Playlist</h1>
    
    <?php if (!empty($success)): ?>
        <div class="success-message">
            <?php echo $success; ?>
            <a href="playlists.php" class="view-playlists-btn">View Your Playlists</a>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="playlist-details">
            <div class="playlist-cover">
                <div class="cover-preview">
                    <img src="assets/images/placeholder.jpg" alt="Playlist Cover" id="cover-preview">
                </div>
                <div class="file-input-container">
                    <input type="file" id="cover_image" name="cover_image" accept="image/*" onchange="previewCover(this)">
                    <label for="cover_image" class="file-input-label">Choose Cover Image</label>
                </div>
            </div>
            
            <div class="playlist-info">
                <div class="form-group">
                    <label for="name">Playlist Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Created By</label>
                    <p class="creator"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="song-selection">
            <h2>Add Songs to Your Playlist</h2>
            
            <?php if (empty($songs)): ?>
                <p class="no-songs">No songs available. Artists need to upload songs first.</p>
            <?php else: ?>
                <div class="song-list">
                    <?php foreach ($songs as $song): ?>
                    <div class="song-item">
                        <div class="song-checkbox">
                            <input type="checkbox" id="song-<?php echo $song['id']; ?>" name="songs[]" value="<?php echo $song['id']; ?>">
                            <label for="song-<?php echo $song['id']; ?>"></label>
                        </div>
                        <div class="song-image">
                            <img src="uploads/covers/<?php echo htmlspecialchars($song['cover_image']); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                        </div>
                        <div class="song-details">
                            <h3><?php echo htmlspecialchars($song['title']); ?></h3>
                            <p><?php echo htmlspecialchars($song['artist_name']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <a href="playlists.php" class="cancel-btn">Cancel</a>
            <button style="margin-bottom: 40px;" type="submit" class="create-btn">Create Playlist</button>
        </div>
    </form>
</div>

<style>
    .create-playlist-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    .create-playlist-container h1 {
        margin-bottom: 20px;
        color: wheat;
    }
    .success-message {
        background-color: rgba(107, 255, 107, 0.2);
        color: #6bff6b;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .view-playlists-btn {
        padding: 5px 10px;
        background-color: rgba(107, 255, 107, 0.3);
        color: #6bff6b;
        border: 1px solid #6bff6b;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
    }
    .error-message {
        background-color: rgba(255, 107, 107, 0.2);
        color: #ff6b6b;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .playlist-details {
        display: flex;
        gap: 30px;
        margin-bottom: 30px;
    }
    .playlist-cover {
        flex: 0 0 200px;
    }
    .cover-preview {
        width: 200px;
        height: 200px;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 15px;
    }
    .cover-preview img {
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
    .playlist-info {
        flex: 1;
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
    .creator {
        color: wheat;
        font-weight: bold;
    }
    .song-selection {
        margin-bottom: 30px;
    }
    .song-selection h2 {
        margin-bottom: 15px;
        color: wheat;
    }
    .no-songs {
        color: #aaa;
        font-style: italic;
    }
    .song-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #333;
        border-radius: 4px;
        padding: 10px;
    }
    .song-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #333;
    }
    .song-item:last-child {
        border-bottom: none;
    }
    .song-checkbox {
        margin-right: 15px;
    }
    .song-checkbox input[type=checkbox] {
        display: none;
    }
    .song-checkbox label {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #555;
        border-radius: 4px;
        position: relative;
        cursor: pointer;
    }
    .song-checkbox input[type=checkbox]:checked + label:after {
        content: '\2714';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: wheat;
        font-size: 14px;
    }
    .song-image {
        width: 50px;
        height: 50px;
        margin-right: 15px;
    }
    .song-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
    }
    .song-details {
        flex: 1;
    }
    .song-details h3 {
        margin-bottom: 5px;
        color: #fff;
    }
    .song-details p {
        color: #aaa;
        font-size: 14px;
    }
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }
    .cancel-btn {
        padding: 10px 20px;
        background-color: transparent;
        color: #aaa;
        border: 1px solid #aaa;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
    }
    .cancel-btn:hover {
        background-color: rgba(170, 170, 170, 0.1);
    }
    .create-btn {
        padding: 10px 20px;
        background-color: wheat;
        color: #121212;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    .create-btn:hover {
        background-color: #e6d9bc;
    }
</style>

<script>
    function previewCover(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('cover-preview').src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>