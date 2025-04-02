<?php
require_once 'includes/header.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's purchased songs
$stmt = $conn->prepare("SELECT s.id, s.title, s.cover_image, a.artist_name 
                        FROM purchases p 
                        JOIN songs s ON p.song_id = s.id 
                        JOIN artists a ON s.artist_id = a.id 
                        WHERE p.user_id = ? 
                        ORDER BY p.purchase_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$purchased_songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's playlists
$stmt = $conn->prepare("SELECT id, name, cover_image FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$playlists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's liked songs
$stmt = $conn->prepare("SELECT s.id, s.title, s.cover_image, a.artist_name 
                        FROM likes l 
                        JOIN songs s ON l.song_id = s.id 
                        JOIN artists a ON s.artist_id = a.id 
                        WHERE l.user_id = ? 
                        ORDER BY l.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$liked_songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's followed artists
$stmt = $conn->prepare("SELECT a.id, a.artist_name, a.category, u.profile_picture 
                        FROM follows f 
                        JOIN artists a ON f.artist_id = a.id 
                        JOIN users u ON a.user_id = u.id 
                        WHERE f.user_id = ? 
                        ORDER BY f.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$followed_artists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

    <div class="library-container">
        <h1>Your Library</h1>

        <!-- Purchased Songs Section -->
        <div class="section">
            <div class="section-header">
                <h2>Purchased Songs</h2>
            </div>
            <?php if (empty($purchased_songs)): ?>
                <p class="empty-message">You haven't purchased any songs yet. <a href="index.php">Browse music</a> to find songs to purchase.</p>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($purchased_songs as $song): ?>
                        <div class="grid-item" onclick="window.location.href='song.php?id=<?php echo $song['id']; ?>'">
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Liked Songs Section -->
        <div class="section">
            <div class="section-header">
                <h2>Liked Songs</h2>
            </div>
            <?php if (empty($liked_songs)): ?>
                <p class="empty-message">You haven't liked any songs yet. Like songs to add them to this section.</p>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($liked_songs as $song): ?>
                        <div class="grid-item" onclick="window.location.href='song.php?id=<?php echo $song['id']; ?>'">
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
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Your Playlists Section -->
        <div class="section">
            <div class="section-header">
                <h2>Your Playlists</h2>
                <a href="create-playlist.php" class="create-btn">Create Playlist</a>
            </div>
            <?php if (empty($playlists)): ?>
                <p class="empty-message">You haven't created any playlists yet. <a href="create-playlist.php">Create a playlist</a> to get started.</p>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($playlists as $playlist): ?>
                        <div class="grid-item" onclick="window.location.href='playlist.php?id=<?php echo $playlist['id']; ?>'">
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
                                <div class="grid-item-subtitle">Your Playlist</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Followed Artists Section -->
        <div class="section">
            <div class="section-header">
                <h2>Followed Artists</h2>
            </div>
            <?php if (empty($followed_artists)): ?>
                <p class="empty-message">You aren't following any artists yet. Follow artists to see them here.</p>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($followed_artists as $artist): ?>
                        <div class="grid-item artist-item" onclick="window.location.href='artist.php?id=<?php echo $artist['id']; ?>'">
                            <div class="grid-item-image">
                                <img src="uploads/profile/<?php echo htmlspecialchars($artist['profile_picture']); ?>" alt="<?php echo htmlspecialchars($artist['artist_name']); ?>" class="artist-image">
                            </div>
                            <div class="grid-item-info">
                                <div class="grid-item-title"><?php echo htmlspecialchars($artist['artist_name']); ?></div>
                                <div class="grid-item-subtitle"><?php echo htmlspecialchars($artist['category']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .library-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .library-container h1 {
            color: wheat;
            margin-bottom: 30px;
            text-align: center;
        }
        .section {
            margin-bottom: 40px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-header h2 {
            color: wheat;
            font-size: 24px;
        }
        .create-btn {
            padding: 8px 16px;
            background-color: wheat;
            color: #121212;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
        }
        .empty-message {
            color: #aaa;
            text-align: center;
            padding: 20px;
            background-color: #1e1e1d;
            border-radius: 8px;
        }
        .empty-message a {
            color: wheat;
            text-decoration: none;
        }
        .empty-message a:hover {
            text-decoration: underline;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .grid-item {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .grid-item:hover {
            transform: translateY(-5px);
        }
        .grid-item-image {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .grid-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .artist-image {
            border-radius: 50% !important;
        }
        .grid-item-overlay {
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
        }
        .grid-item:hover .grid-item-overlay {
            opacity: 1;
        }
        .play-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: wheat;
            color: #121212;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .play-button i {
            font-size: 20px;
        }
        .grid-item-info {
            padding: 0 5px;
        }
        .grid-item-title {
            font-weight: bold;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .grid-item-subtitle {
            color: #aaa;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    <script>
        // Play button functionality
        const playButtons = document.querySelectorAll('.play-button');
        playButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();

                const id = this.dataset.id;
                const type = this.dataset.type || 'song';

                // In a real app, you would set up an audio element to play the file
                console.log(`Playing ${type} with ID: ${id}`);
            });
        });
    </script>

<?php require_once 'includes/footer.php'; ?>