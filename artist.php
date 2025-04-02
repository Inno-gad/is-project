<?php
require_once 'includes/header.php';

// Get artist ID from URL
$artist_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($artist_id <= 0) {
    header("Location: index.php");
    exit();
}

// Get artist details
$stmt = $conn->prepare("SELECT a.id, a.artist_name, a.category, a.bio, u.profile_picture, u.id as user_id 
                        FROM artists a 
                        JOIN users u ON a.user_id = u.id 
                        WHERE a.id = ?");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$artist = $result->fetch_assoc();

// Check if user follows the artist
$following = false;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $following = followsArtist($conn, $user_id, $artist_id);
}

// Get follower count
$follower_count = getArtistFollowers($conn, $artist_id);

// Get artist's songs
$stmt = $conn->prepare("SELECT id, title, album, cover_image, price FROM songs WHERE artist_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="artist-detail-container">
    <div class="artist-header">
        <div class="artist-image">
            <img src="uploads/profile/<?php echo htmlspecialchars($artist['profile_picture']); ?>" alt="<?php echo htmlspecialchars($artist['artist_name']); ?>">
        </div>
        
        <div class="artist-info">
            <h1><?php echo htmlspecialchars($artist['artist_name']); ?></h1>
            <div class="artist-category"><?php echo htmlspecialchars($artist['category']); ?></div>
            
            <div class="artist-stats">
                <div class="stat">
                    <span class="stat-value"><?php echo count($songs); ?></span>
                    <span class="stat-label">Songs</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?php echo $follower_count; ?></span>
                    <span class="stat-label">Followers</span>
                </div>
            </div>
            
            <?php if (isLoggedIn() && $user_id != $artist['user_id']): ?>
            <button class="follow-btn <?php echo $following ? 'following' : ''; ?>" data-id="<?php echo $artist_id; ?>">
                <?php echo $following ? 'Following' : 'Follow'; ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($artist['bio'])): ?>
    <div class="artist-bio">
        <h2>About</h2>
        <p><?php echo nl2br(htmlspecialchars($artist['bio'])); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="artist-songs">
        <h2>Songs</h2>
        
        <?php if (empty($songs)): ?>
            <p class="no-songs">This artist hasn't uploaded any songs yet.</p>
        <?php else: ?>
            <div class="songs-list">
                <?php foreach ($songs as $song): ?>
                <div class="song-item" onclick="window.location.href='song.php?id=<?php echo $song['id']; ?>'">
                    <div class="song-image">
                        <img src="uploads/covers/<?php echo htmlspecialchars($song['cover_image']); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                        <div class="play-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    <div class="song-details">
                        <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                        <?php if (!empty($song['album'])): ?>
                        <div class="song-album"><?php echo htmlspecialchars($song['album']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="song-price">
                        $<?php echo number_format($song['price'], 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .artist-detail-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .artist-header {
        display: flex;
        gap: 30px;
        margin-bottom: 40px;
    }
    .artist-image {
        flex: 0 0 200px;
    }
    .artist-image img {
        width: 100%;
        height: auto;
        border-radius: 50%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    .artist-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .artist-info h1 {
        font-size: 32px;
        margin-bottom: 10px;
        color: wheat;
    }
    .artist-category {
        color: #aaa;
        font-size: 18px;
        margin-bottom: 20px;
    }
    .artist-stats {
        display: flex;
        gap: 30px;
        margin-bottom: 20px;
    }
    .stat {
        display: flex;
        flex-direction: column;
    }
    .stat-value {
        font-size: 24px;
        font-weight: bold;
    }
    .stat-label {
        color: #aaa;
        font-size: 14px;
    }
    .follow-btn {
        align-self: flex-start;
        padding: 10px 20px;
        background-color: transparent;
        color: wheat;
        border: 1px solid wheat;
        border-radius: 20px;
        cursor: pointer;
        font-weight: bold;
    }
    .follow-btn.following {
        background-color: wheat;
        color: #121212;
    }
    .artist-bio {
        margin-bottom: 40px;
    }
    .artist-bio h2 {
        color: wheat;
        margin-bottom: 15px;
    }
    .artist-bio p {
        line-height: 1.6;
    }
    .artist-songs h2 {
        color: wheat;
        margin-bottom: 20px;
    }
    .no-songs {
        color: #aaa;
        font-style: italic;
    }
    .songs-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    .song-item {
        display: flex;
        align-items: center;
        background-color: #1e1e1d;
        border-radius: 8px;
        padding: 10px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .song-item:hover {
        background-color: #2a2a2a;
    }
    .song-image {
        position: relative;
        width: 60px;
        height: 60px;
        margin-right: 15px;
    }
    .song-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 4px;
    }
    .play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
        border-radius: 4px;
    }
    .song-item:hover .play-overlay {
        opacity: 1;
    }
    .play-overlay i {
        color: white;
        font-size: 24px;
    }
    .song-details {
        flex: 1;
    }
    .song-title {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .song-album {
        color: #aaa;
        font-size: 14px;
    }
    .song-price {
        color: wheat;
        font-weight: bold;
    }
</style>

<script>
    // Follow button functionality
    const followBtn = document.querySelector('.follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', function() {
            const artistId = this.dataset.id;
            
            // Send AJAX request to toggle follow
            const formData = new FormData();
            formData.append('action', 'toggle_follow');
            formData.append('artist_id', artistId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    if (data.following) {
                        this.classList.add('following');
                        this.textContent = 'Following';
                    } else {
                        this.classList.remove('following');
                        this.textContent = 'Follow';
                    }
                    
                    // Update follower count in stats
                    const followerCountEl = document.querySelector('.stat-value');
                    followerCountEl.textContent = data.count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>