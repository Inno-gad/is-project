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
        border: 1px solid wheat;
        border-radius: 20px;
        cursor: pointer;
        font-size: 14px;
    }
    .follow-btn.following {
        background-color: wheat;
        color: #121212;
    }
    .follower-count {
        margin-left: 5px;
        font-size: 12px;
        opacity: 0.8;
    }
    .album-info {
        color: #aaa;
        margin-bottom: 20px;
    }
    .song-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    .play-btn, .like-btn, .share-btn, .buy-btn, .owned-btn {
        padding: 10px 15px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .play-btn {
        background-color: wheat;
        color: #121212;
        border: none;
    }
    .like-btn {
        background-color: transparent;
        color: #fff;
        border: 1px solid #fff;
    }
    .like-btn.liked {
        color: #ff6b6b;
        border-color: #ff6b6b;
    }
    .share-btn {
        background-color: transparent;
        color: #fff;
        border: 1px solid #fff;
    }
    .buy-btn {
        background-color: #6bff6b;
        color: #121212;
        border: none;
    }
    .owned-btn {
        background-color: #555;
        color: #fff;
        border: none;
        opacity: 0.7;
    }
    .like-count {
        font-size: 12px;
    }
    .song-content {
        margin-top: 40px;
    }
    .related-songs h2 {
        margin-bottom: 20px;
        color: wheat;
    }
    .related-songs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    .related-song-item {
        cursor: pointer;
        transition: transform 0.2s;
    }
    .related-song-item:hover {
        transform: scale(1.05);
    }
    .related-song-item img {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        border-radius: 4px;
    }
    .related-song-title {
        margin-top: 5px;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .no-related {
        color: #aaa;
        font-style: italic;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
    }
    .modal-content {
        background-color: #1e1e1d;
        margin: 15% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 500px;
    }
    .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close-modal:hover {
        color: #fff;
    }
    .modal h2 {
        margin-bottom: 20px;
        color: wheat;
    }
    .share-options {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    .share-option {
        padding: 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-weight: bold;
    }
    .share-option[data-method="facebook"] {
        background-color: #3b5998;
        color: white;
    }
    .share-option[data-method="twitter"] {
        background-color: #1da1f2;
        color: white;
    }
    .share-option[data-method="whatsapp"] {
        background-color: #25d366;
        color: white;
    }
    .share-option[data-method="email"] {
        background-color: #ea4335;
        color: white;
    }
    .share-option[data-method="copy"] {
        background-color: #555;
        color: white;
    }
</style>

<script>
    // Play button functionality
    const playBtn = document.querySelector('.play-btn');
    if (playBtn) {
        playBtn.addEventListener('click', function() {
            const audioFile = this.dataset.file;
            // Update the audio player
            document.getElementById('current-song-cover').src = document.querySelector('.song-cover img').src;
            document.getElementById('current-song-title').textContent = '<?php echo addslashes($song['title']); ?>';
            document.getElementById('current-song-artist').textContent = '<?php echo addslashes($song['artist_name']); ?>';
            
            // Toggle play state
            const playPauseBtn = document.getElementById('play-pause-button');
            playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
            window.isPlaying = true;
            
            // In a real app, you would set up an audio element to play the file
            console.log(`Playing: ${audioFile}`);
        });
    }
    
    // Like button functionality
    const likeBtn = document.querySelector('.like-btn');
    if (likeBtn) {
        likeBtn.addEventListener('click', function() {
            const songId = this.dataset.id;
            
            // Send AJAX request to toggle like
            const formData = new FormData();
            formData.append('action', 'toggle_like');
            formData.append('song_id', songId);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    if (data.liked) {
                        this.classList.add('liked');
                        this.querySelector('i').classList.replace('far', 'fas');
                    } else {
                        this.classList.remove('liked');
                        this.querySelector('i').classList.replace('fas', 'far');
                    }
                    
                    // Update like count
                    this.querySelector('.like-count').textContent = data.count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
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
                        this.textContent = 'Following ';
                    } else {
                        this.classList.remove('following');
                        this.textContent = 'Follow ';
                    }
                    
                    // Update follower count
                    const countSpan = document.createElement('span');
                    countSpan.className = 'follower-count';
                    countSpan.textContent = data.count;
                    this.appendChild(countSpan);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Share button and modal functionality
    const shareBtn = document.querySelector('.share-btn');
    const shareModal = document.getElementById('share-modal');
    const closeModal = document.querySelector('.close-modal');
    const shareOptions = document.querySelectorAll('.share-option');
    
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            shareModal.style.display = 'block';
        });
    }
    
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            shareModal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === shareModal) {
            shareModal.style.display = 'none';
        }
    });
    
    // Share options functionality
    if (shareOptions) {
        shareOptions.forEach(option => {
            option.addEventListener('click', function() {
                const method = this.dataset.method;
                const songId = document.querySelector('.share-btn').dataset.id;
                const songUrl = `${window.location.origin}${window.location.pathname}?id=${songId}`;
                const songTitle = '<?php echo addslashes($song['title']); ?>';
                const artistName = '<?php echo addslashes($song['artist_name']); ?>';
                const shareText = `Check out "${songTitle}" by ${artistName} on InSync!`;
                
                // Handle different share methods
                switch (method) {
                    case 'facebook':
                        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(songUrl)}`, '_blank');
                        break;
                    case 'twitter':
                        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(songUrl)}`, '_blank');
                        break;
                    case 'whatsapp':
                        window.open(`https://wa.me/?text=${encodeURIComponent(shareText + ' ' + songUrl)}`, '_blank');
                        break;
                    case 'email':
                        window.open(`mailto:?subject=${encodeURIComponent(shareText)}&body=${encodeURIComponent(shareText + '\n\n' + songUrl)}`, '_blank');
                        break;
                    case 'copy':
                        navigator.clipboard.writeText(songUrl).then(() => {
                            alert('Link copied to clipboard!');
                        });
                        break;
                }
                
                // Record share action
                const formData = new FormData();
                formData.append('action', 'share_song');
                formData.append('song_id', songId);
                formData.append('share_method', method);
                
                fetch('api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    shareModal.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    }
    
    // Buy button functionality
    const buyBtn = document.querySelector('.buy-btn');
    if (buyBtn) {
        buyBtn.addEventListener('click', function() {
            const songId = this.dataset.id;
            const price = this.dataset.price;
            
            // Send AJAX request to add to cart
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('song_id', songId);
            formData.append('price', price);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    
                    // Update cart count in header if available
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = `(${data.cart_count})`;
                    }
                    
                    // Disable buy button and change to "Added to Cart"
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
                    this.style.backgroundColor = '#555';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart');
            });
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>