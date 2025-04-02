<?php
require_once 'includes/header.php';

// Check if user is logged in and is an artist
if (!isLoggedIn() || !isArtist()) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get artist data
$artist = getArtistData($conn, $user_id);

// Process song upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $album = $_POST['album'];
    $price = $_POST['price'];
    
    // Validate input
    if (empty($title) || empty($price)) {
        $error = "Please fill in all required fields";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Please enter a valid price";
    } else {
        // Handle cover image upload
        $cover_image = 'placeholder.jpg'; // Default cover
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
            $allowed_images = array('jpg', 'jpeg', 'png', 'webp');
            $filename = $_FILES['cover_image']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed_images)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/covers/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    $cover_image = $new_filename;
                } else {
                    $error = "Error uploading cover image";
                }
            } else {
                $error = "Invalid cover image type. Only JPG, JPEG, PNG, and WEBP files are allowed.";
            }
        }
        
        // Handle song file upload
        if (isset($_FILES['song_file']) && $_FILES['song_file']['error'] == 0) {
            $allowed_audio = array('mp3', 'wav', 'ogg');
            $filename = $_FILES['song_file']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed_audio)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/songs/';

                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $upload_path = $upload_dir; // Correctly assign $upload_path


                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['song_file']['tmp_name'], $upload_path)) {
                    $file_path = $new_filename;
                    
                    // Insert song
                    $stmt = $conn->prepare("INSERT INTO songs (title, artist_id, album, cover_image, file_path, price) VALUES (?, ?, ?, ?, ?, ?)");

                    $stmt->bind_param("sisssd", $title, $artist['id'], $album, $cover_image, $file_path, $price);


                    if ($stmt->execute()) {
                        $success = "Song uploaded successfully!";
                    } else {
                        $error = "Error uploading song: " . $conn->error;
                    }
                } else {
                    $error = "Error uploading song file";
                }
            } else {
                $error = "Invalid audio file type. Only MP3, WAV, and OGG files are allowed.";
            }
        } else {
            $error = "Please upload a song file";
        }
    }
}

// Get artist's songs
$stmt = $conn->prepare("SELECT id, title, album, cover_image, file_path FROM songs WHERE artist_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $artist['id']);
$stmt->execute();
$songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="artist-dashboard-container">
    <h1>Artist Dashboard</h1>
    
    <?php if (!empty($success)): ?>
        <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="dashboard-tabs">
        <div class="tab active" data-tab="upload">Upload Song</div>
        <div class="tab" data-tab="songs">My Songs</div>
        <div class="tab" data-tab="stats">Statistics</div>
    </div>
    
    <div class="dashboard-content">
        <div class="tab-pane active" id="upload-tab">
            <h2>Upload New Song</h2>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Song Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="album">Album</label>
                        <input type="text" id="album" name="album">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" id="price" name="price" min="0.99" step="0.01" value="0.99" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cover_image">Cover Image</label>
                        <div class="file-input-container">
                            <input type="file" id="cover_image" name="cover_image" accept="image/*">
                            <label for="cover_image" class="file-input-label">Choose Cover Image</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="song_file">Song File (MP3, WAV, OGG) *</label>
                    <div class="file-input-container">
                        <input type="file" id="song_file" name="song_file" accept=".mp3,.wav,.ogg" required>
                        <label for="song_file" class="file-input-label">Choose Song File</label>
                        <span id="selected-file">No file chosen</span>

                    </div>
                    <button type="submit" class="upload-btn">Upload Song</button>
                </div>
                

            </form>
        </div>
        
        <div class="tab-pane" id="songs-tab">
            <h2>My Songs</h2>
            
            <?php if (empty($songs)): ?>
                <div class="empty-songs">
                    <p>You haven't uploaded any songs yet</p>
                    <button class="upload-now-btn" onclick="switchTab('upload')">Upload Now</button>
                </div>
            <?php else: ?>
                <div class="songs-list">
                    <div class="song-header">
                        <div class="song-title">Title</div>
                        <div class="song-album">Album</div>
                        <div class="song-actions">Actions</div>
                    </div>
                    <div class="play-song">
                        <audio src="uploads/songs/golden.mp3"></audio>
                    </div>
                    
                    <?php foreach ($songs as $song): ?>
                    <div class="song-item">
                        <div class="song-title">
                            <img src="uploads/covers/<?php echo htmlspecialchars($song['cover_image']); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                            <span><?php echo htmlspecialchars($song['title']); ?></span>
                        </div>
                        <div class="song-album"><?php echo htmlspecialchars($song['album']); ?></div>
                        <div class="song-actions">
                            <button class="play-btn" data-file="uploads/songs/<?php echo htmlspecialchars($song['file_path']); ?>"><i class="fas fa-play"></i></button>
                            <button class="edit-btn" data-id="<?php echo $song['id']; ?>"><i class="fas fa-edit"></i></button>
                            <button class="delete-btn" data-id="<?php echo $song['id']; ?>"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-pane" id="stats-tab">
            <h2>Statistics</h2>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-music"></i></div>
                    <div class="stat-value"><?php echo count($songs); ?></div>
                    <div class="stat-label">Total Songs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-play"></i></div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Total Plays</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-value">0</div>
                    <div class="stat-label">Total Sales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-value">$0.00</div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .artist-dashboard-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .artist-dashboard-container h1 {
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
    .dashboard-tabs {
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
    .tab-pane h2 {
        margin-bottom: 20px;
        color: wheat;
    }
    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }
    .form-group {
        flex: 1;
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #fff;
    }
    .form-group input[type="text"],
    .form-group input[type="number"] {
        width: 100%;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #333;
        background-color: #2a2a2a;
        color: #fff;
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
    #selected-file {
        margin-left: 10px;
        color: #aaa;
    }
    .upload-btn {
        padding: 10px 20px;
        background-color: wheat;
        color: #121212;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        margin-top: 10px;
    }
    .upload-btn:hover {
        background-color: #e6d9bc;
    }
    .empty-songs {
        text-align: center;
        padding: 50px 0;
    }
    .empty-songs p {
        font-size: 18px;
        color: #aaa;
        margin-bottom: 20px;
    }
    .upload-now-btn {
        padding: 10px 20px;
        background-color: wheat;
        color: #121212;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    .upload-now-btn:hover {
        background-color: #e6d9bc;
    }
    .songs-list {
        border: 1px solid #333;
        border-radius: 4px;
    }
    .song-header {
        display: flex;
        padding: 10px;
        background-color: #1e1e1d;
        border-bottom: 1px solid #333;
        font-weight: bold;
    }
    .song-title {
        flex: 2;
        display: flex;
        align-items: center;
    }
    .song-album {
        flex: 1;
    }
    .song-actions {
        flex: 1;
        text-align: right;
    }
    .song-item {
        display: flex;
        padding: 10px;
        border-bottom: 1px solid #333;
        align-items: center;
    }
    .song-item:last-child {
        border-bottom: none;
    }
    .song-item .song-title img {
        width: 40px;
        height: 40px;
        margin-right: 10px;
        border-radius: 4px;
    }
    .play-btn, .edit-btn, .delete-btn {
        background: none;
        border: none;
        cursor: pointer;
        margin-left: 10px;
        font-size: 16px;
    }
    .play-btn {
        color: wheat;
    }
    .edit-btn {
        color: #6bff6b;
    }
    .delete-btn {
        color: #ff6b6b;
    }
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    .stat-card {
        background-color: #1e1e1d;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    .stat-icon {
        font-size: 24px;
        color: wheat;
        margin-bottom: 10px;
    }
    .stat-value {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
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
            switchTab(this.dataset.tab);
        });
    });
    
    function switchTab(tabId) {
        // Remove active class from all tabs and panes
        tabs.forEach(t => t.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding pane
        document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
        document.getElementById(`${tabId}-tab`).classList.add('active');
    }
    
    // Display selected file name
    document.getElementById('song_file').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        document.getElementById('selected-file').textContent = fileName;
    });
    
    // Play song
    const playButtons = document.querySelectorAll('.play-btn');
    playButtons.forEach(button => {
        button.addEventListener('click', function() {
            const audioFile = this.dataset.file;
            // Update the audio player
            document.getElementById('current-song-cover').src = this.closest('.song-item').querySelector('img').src;
            document.getElementById('current-song-title').textContent = this.closest('.song-item').querySelector('.song-title span').textContent;
            document.getElementById('current-song-artist').textContent = '<?php echo htmlspecialchars($artist['artist_name']); ?>';
            
            // Play the song (in a real app, you would set up an audio element)
            console.log(`Playing: ${audioFile}`);
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>