<?php
require_once 'includes/header.php';

// Get song ID from URL
$song_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($song_id <= 0) {
    header("Location: index.php");
    exit();
}

// Get song details
$stmt = $conn->prepare("SELECT s.id, s.title, s.album, s.cover_image, s.file_path, s.price, a.id as artist_id, a.artist_name, a.category 
                        FROM songs s 
                        JOIN artists a ON s.artist_id = a.id 
                        WHERE s.id = ?");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$song = $result->fetch_assoc();

// Check if user has liked the song
$liked = false;
$purchased = false;
$following = false;

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $liked = hasLikedSong($conn, $user_id, $song_id);
    $purchased = hasPurchasedSong($conn, $user_id, $song_id);
    $following = followsArtist($conn, $user_id, $song['artist_id']);
}

// Get like count
$like_count = getSongLikes($conn, $song_id);

// Get follower count
$follower_count = getArtistFollowers($conn, $song['artist_id']);
?>

<div class="song-detail-container">
    <div class="song-header">
        <div class="song-cover">
            <img src="uploads/covers/<?php echo htmlspecialchars($song['cover_image']); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
        </div>
        
        <div class="song-info">
            <h1><?php echo htmlspecialchars($song['title']); ?></h1>
            <div class="artist-info">
                <a href="artist.php?id=<?php echo $song['artist_id']; ?>" class="artist-link">
                    <?php echo htmlspecialchars($song['artist_name']); ?>
                </a>
                <?php if (isLoggedIn()): ?>
                <button class="follow-btn <?php echo $following ? 'following' : ''; ?>" data-id="<?php echo $song['artist_id']; ?>">
                    <?php echo $following ? 'Following' : 'Follow'; ?>
                    <span class="follower-count"><?php echo $follower_count; ?></span>
                </button>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($song['album'])): ?>
            <div class="album-info">
                Album: <?php echo htmlspecialchars($song['album']); ?>
            </div>
            <?php endif; ?>
            
            <div class="song-actions">
                <button class="play-btn" data-file="uploads/songs/<?php echo htmlspecialchars($song['file_path']); ?>">
                    <i class="fas fa-play"></i> Play
                </button>
                
                <?php if (isLoggedIn()): ?>
                <button class="like-btn <?php echo $liked ? 'liked' : ''; ?>" data-id="<?php echo $song_id; ?>">
                    <i class="<?php echo $liked ? 'fas' : 'far'; ?> fa-heart"></i>
                    <span class="like-count"><?php echo $like_count; ?></span>
                </button>
                
                <button class="share-btn" data-id="<?php echo $song_id; ?>">
                    <i class="fas fa-share-alt"></i> Share
                </button>
                
                <?php if (!$purchased): ?>
                <button class="buy-btn" data-id="<?php echo $song_id; ?>" data-price="<?php echo $song['price']; ?>">
                    <i class="fas fa-shopping-cart"></i> Buy $<?php echo number_format($song['price'], 2); ?>
                </button>
                <?php else: ?>
                <button class="owned-btn" disabled>
                    <i class="fas fa-check"></i> Owned
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="song-content">
        <div class="related-songs">
            <h2>More from <?php echo htmlspecialchars($song['artist_name']); ?></h2>
            
            <?php
            // Get more songs from the same artist
            $stmt = $conn->prepare("SELECT id, title, cover_image FROM songs WHERE artist_id = ? AND id != ? LIMIT 5");
            $stmt->bind_param("ii", $song['artist_id'], $song_id);
            $stmt->execute();
            $related_songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (!empty($related_songs)):
            ?>
            <div class="related-songs-grid">
                <?php foreach ($related_songs as $related): ?>
                <div class="related-song-item" onclick="window.location.href='song.php?id=<?php echo $related['id']; ?>'">
                    <img src="uploads/covers/<?php echo htmlspecialchars($related['cover_image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>">
                    <div class="related-song-title"><?php echo htmlspecialchars($related['title']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="no-related">No other songs available from this artist.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Share This Song</h2>
        <div class="share-options">
            <button class="share-option" data-method="facebook">
                <i class="fab fa-facebook"></i> Facebook
            </button>
            <button class="share-option" data-method="twitter">
                <i class="fab fa-twitter"></i> Twitter
            </button>
            <button class="share-option" data-method="whatsapp">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
            <button class="share-option" data-method="email">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button class="share-option" data-method="copy">
                <i class="fas fa-link"></i> Copy Link
            </button>
        </div>
    </div>
</div>

<style>
    .song-detail-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .song-header {
        display: flex;
        gap: 30px;
        margin-bottom: 40px;
    }
    .song-cover {
        flex: 0 0 300px;
    }
    .song-cover img {
        width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    .song-info {
        flex: 1;
    }
    .song-info h1 {
        font-size: 32px;
        margin-bottom: 10px;
        color: wheat;
    }
    .artist-info {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    .artist-link {
        color: #fff;
        font-size: 18px;
        text-decoration: none;
        margin-right: 15px;
    }
    .artist-link:hover {
        text-decoration: underline;
    }
    .follow-btn {
        padding: 5px 10px;
        background-color: transparent;
        color: wheat;
        border: