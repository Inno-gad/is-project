<?php
require_once 'includes/header.php';

// Get featured songs
$stmt = $conn->prepare("SELECT s.id, s.title, s.cover_image, s.price, a.id as artist_id, a.artist_name 
                        FROM songs s 
                        JOIN artists a ON s.artist_id = a.id 
                        ORDER BY s.created_at DESC 
                        LIMIT 8");
$stmt->execute();
$featured_songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get featured artists
$stmt = $conn->prepare("SELECT a.id, a.artist_name, a.category, u.profile_picture 
                        FROM artists a 
                        JOIN users u ON a.user_id = u.id 
                        ORDER BY RAND() 
                        LIMIT 4");
$stmt->execute();
$featured_artists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get featured playlists
$stmt = $conn->prepare("SELECT p.id, p.name, p.cover_image, p.price, u.username 
                        FROM playlists p 
                        JOIN users u ON p.user_id = u.id 
                        ORDER BY p.created_at DESC 
                        LIMIT 4");
$stmt->execute();
$featured_playlists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check user likes and follows if logged in
$user_likes = [];
$user_follows = [];
$user_purchases = [];

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];

    // Get user likes
    $stmt = $conn->prepare("SELECT song_id FROM likes WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_likes[] = $row['song_id'];
    }

    // Get user follows
    $stmt = $conn->prepare("SELECT artist_id FROM follows WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_follows[] = $row['artist_id'];
    }

    // Get user purchases
    $stmt = $conn->prepare("SELECT song_id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_purchases[] = $row['song_id'];
    }
}
?>

    <div id="home-tab" class="tab-content active">
        <!-- Featured Songs Section -->
        <div class="section">
            <div class="section-header">
                <h2>Featured Songs</h2>
                <a href="#" class="view-all">View All</a>
            </div>
            <div id="featured-songs" class="grid-container">
                <?php foreach ($featured_songs as $song): ?>
                    <div class="grid-item" data-id="<?php echo $song['id']; ?>">
                        <div class="grid-item-image">
                            <img src="uploads/covers/<?php echo htmlspecialchars($song['cover_image']); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                            <div class="grid-item-overlay">
                                <button class="play-button" data-id="<?php echo $song['id']; ?>">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid-item-info">
                            <div class="grid-item-title"><?php echo htmlspecialchars($song['title']); ?></div>
                            <div class="grid-item-subtitle"><?php echo htmlspecialchars($song['artist_name']); ?></div>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <div class="grid-item-actions">
                                <button class="like-btn <?php echo in_array($song['id'], $user_likes) ? 'liked' : ''; ?>" data-id="<?php echo $song['id']; ?>" title="Like">
                                    <i class="<?php echo in_array($song['id'], $user_likes) ? 'fas' : 'far'; ?> fa-heart"></i>
                                </button>
                                <button class="share-btn" data-id="<?php echo $song['id']; ?>" data-title="<?php echo htmlspecialchars($song['title']); ?>" data-artist="<?php echo htmlspecialchars($song['artist_name']); ?>" title="Share">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                                <?php if (!in_array($song['id'], $user_purchases)): ?>
                                    <button class="buy-btn" data-id="<?php echo $song['id']; ?>" data-price="<?php echo $song['price']; ?>" title="Buy for $<?php echo number_format($song['price'], 2); ?>">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="owned-btn" disabled title="In Cart">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <a href="song.php?id=<?php echo $song['id']; ?>" class="grid-item-link"></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Featured Artists Section -->
        <div class="section">
            <div class="section-header">
                <h2>Featured Artists</h2>
                <a href="#" class="view-all">View All</a>
            </div>
            <div id="featured-artists" class="grid-container">
                <?php foreach ($featured_artists as $artist): ?>
                    <div class="grid-item artist-item" data-id="<?php echo $artist['id']; ?>">
                        <div class="grid-item-image">
                            <img src="uploads/profile/<?php echo htmlspecialchars($artist['profile_picture']); ?>" alt="<?php echo htmlspecialchars($artist['artist_name']); ?>" class="artist-image">
                        </div>
                        <div class="grid-item-info">
                            <div class="grid-item-title"><?php echo htmlspecialchars($artist['artist_name']); ?></div>
                            <div class="grid-item-subtitle"><?php echo htmlspecialchars($artist['category']); ?></div>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <div class="grid-item-actions">
                                <button class="follow-btn <?php echo in_array($artist['id'], $user_follows) ? 'following' : ''; ?>" data-id="<?php echo $artist['id']; ?>" title="<?php echo in_array($artist['id'], $user_follows) ? 'Following' : 'Follow'; ?>">
                                    <i class="<?php echo in_array($artist['id'], $user_follows) ? 'fas' : 'far'; ?> fa-user-circle"></i>
                                </button>
                                <button class="share-btn" data-id="<?php echo $artist['id']; ?>" data-title="<?php echo htmlspecialchars($artist['artist_name']); ?>" data-type="artist" title="Share">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        <?php endif; ?>

                        <a href="artist.php?id=<?php echo $artist['id']; ?>" class="grid-item-link"></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Featured Playlists Section -->
        <div class="section">
            <div class="section-header">
                <h2>Featured Playlists</h2>
                <a href="#" class="view-all">View All</a>
            </div>
            <div id="featured-playlists" class="grid-container">
                <?php foreach ($featured_playlists as $playlist): ?>
                    <div class="grid-item" data-id="<?php echo $playlist['id']; ?>">
                        <div class="grid-item-image">
                            <img src="uploads/playlists/<?php echo htmlspecialchars($playlist['cover_image']); ?>" alt="<?php echo htmlspecialchars($playlist['name']); ?>">
                            <div class="grid-item-overlay">
                                <button class="play-button" data-id="<?php echo $playlist['id']; ?>" data-type="playlist">
                                    <i class="fas fa-play"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid-item-info">
                            <div class="grid-item-title"><?php echo htmlspecialchars($playlist['name']); ?></div>
                            <div class="grid-item-subtitle">By <?php echo htmlspecialchars($playlist['username']); ?></div>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <div class="grid-item-actions">
                                <button class="share-btn" data-id="<?php echo $playlist['id']; ?>" data-title="<?php echo htmlspecialchars($playlist['name']); ?>" data-type="playlist" title="Share">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                            </div>
                        <?php endif; ?>

                        <a href="playlist.php?id=<?php echo $playlist['id']; ?>" class="grid-item-link"></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div id="share-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Share</h2>
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
        .grid-item {
            position: relative;
        }
        .grid-item-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 10;
        }
        .grid-item:hover .grid-item-actions {
            opacity: 1;
        }
        .like-btn, .share-btn, .follow-btn, .buy-btn, .owned-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            transition: all 0.2s;
        }
        .like-btn:hover, .share-btn:hover, .follow-btn:hover, .buy-btn:hover {
            transform: scale(1.1);
        }
        .like-btn.liked {
            color: #ff6b6b;
        }
        .follow-btn.following {
            color: wheat;
        }
        .buy-btn {
            background-color: rgba(107, 255, 107, 0.7);
            color: #121212;
        }
        .owned-btn {
            background-color: rgba(85, 85, 85, 0.7);
            cursor: default;
        }
        .grid-item-link {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 5;
        }
        .grid-item-actions button {
            z-index: 15;
        }
        .artist-image {
            border-radius: 50%;
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
        // Like button functionality
        const likeButtons = document.querySelectorAll('.like-btn');
        likeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

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
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Follow button functionality
        const followButtons = document.querySelectorAll('.follow-btn');
        followButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

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
                                this.querySelector('i').classList.replace('far', 'fas');
                                this.title = 'Following';
                            } else {
                                this.classList.remove('following');
                                this.querySelector('i').classList.replace('fas', 'far');
                                this.title = 'Follow';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Share button and modal functionality
        const shareButtons = document.querySelectorAll('.share-btn');
        const shareModal = document.getElementById('share-modal');
        const closeModal = document.querySelector('.close-modal');
        const shareOptions = document.querySelectorAll('.share-option');

        let currentShareData = {
            id: null,
            type: 'song',
            title: '',
            artist: ''
        };

        shareButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Set current share data
                currentShareData.id = this.dataset.id;
                currentShareData.type = this.dataset.type || 'song';
                currentShareData.title = this.dataset.title || '';
                currentShareData.artist = this.dataset.artist || '';

                // Update modal title
                const modalTitle = shareModal.querySelector('h2');
                if (currentShareData.type === 'song') {
                    modalTitle.textContent = `Share "${currentShareData.title}" by ${currentShareData.artist}`;
                } else if (currentShareData.type === 'artist') {
                    modalTitle.textContent = `Share Artist: ${currentShareData.title}`;
                } else if (currentShareData.type === 'playlist') {
                    modalTitle.textContent = `Share Playlist: ${currentShareData.title}`;
                }

                // Show modal
                shareModal.style.display = 'block';
            });
        });

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
                    let shareUrl = '';
                    let shareText = '';

                    // Build URL based on content type
                    if (currentShareData.type === 'song') {
                        shareUrl = `${window.location.origin}/song.php?id=${currentShareData.id}`;
                        shareText = `Check out "${currentShareData.title}" by ${currentShareData.artist} on InSync!`;
                    } else if (currentShareData.type === 'artist') {
                        shareUrl = `${window.location.origin}/artist.php?id=${currentShareData.id}`;
                        shareText = `Check out ${currentShareData.title} on InSync!`;
                    } else if (currentShareData.type === 'playlist') {
                        shareUrl = `${window.location.origin}/playlist.php?id=${currentShareData.id}`;
                        shareText = `Check out this playlist: ${currentShareData.title} on InSync!`;
                    }

                    // Handle different share methods
                    switch (method) {
                        case 'facebook':
                            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`, '_blank');
                            break;
                        case 'twitter':
                            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareUrl)}`, '_blank');
                            break;
                        case 'whatsapp':
                            window.open(`https://wa.me/?text=${encodeURIComponent(shareText + ' ' + shareUrl)}`, '_blank');
                            break;
                        case 'email':
                            window.open(`mailto:?subject=${encodeURIComponent(shareText)}&body=${encodeURIComponent(shareText + '\n\n' + shareUrl)}`, '_blank');
                            break;
                        case 'copy':
                            navigator.clipboard.writeText(shareUrl).then(() => {
                                alert('Link copied to clipboard!');
                            });
                            break;
                    }

                    // Record share action if it's a song
                    if (currentShareData.type === 'song') {
                        const formData = new FormData();
                        formData.append('action', 'share_song');
                        formData.append('song_id', currentShareData.id);
                        formData.append('share_method', method);

                        fetch('api.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                console.log(data);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                    }

                    // Close modal
                    shareModal.style.display = 'none';
                });
            });
        }

        // Buy button functionality
        const buyButtons = document.querySelectorAll('.buy-btn');
        buyButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const songId = this.dataset.id;
                const price = this.dataset.price;
                const button = this; // Store reference to the button

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
                            // Update cart count in header
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = `(${data.cart_count})`;
                            }

                            // Disable buy button and change to "Added to Cart"
                            button.disabled = true;
                            button.innerHTML = '<i class="fas fa-check"></i>';
                            button.classList.remove('buy-btn');
                            button.classList.add('owned-btn');
                            button.title = 'In Cart';

                            // Redirect to cart page
                            window.location.href = 'cart.php';
                        } else {
                            alert(data.message || 'Error adding to cart');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while adding to cart');
                    });
            });
        });
    </script>

<?php require_once 'includes/footer.php'; ?>