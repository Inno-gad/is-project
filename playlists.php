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

// Get user's playlists
$stmt = $conn->prepare("SELECT id, name, cover_image FROM playlists WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$playlists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

    <div id="playlists-tab" class="tab-content active">
        <div class="section-header">
            <h2>Your Playlists</h2>
            <a href="create-playlist.php" class="action-btn">Create Playlist</a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($playlists)): ?>
            <div class="empty-playlists">
                <i class="fas fa-list-ul"></i>
                <p>You don't have any playlists yet</p>
                <a href="create-playlist.php" class="create-playlist-btn">Create Your First Playlist</a>
            </div>
        <?php else: ?>
            <div id="user-playlists-container" class="grid-container">
                <?php foreach ($playlists as $playlist): ?>
                    <div class="grid-item" data-id="<?php echo $playlist['id']; ?>">
                        <img src="uploads/playlists/<?php echo htmlspecialchars($playlist['cover_image']); ?>" alt="<?php echo htmlspecialchars($playlist['name']); ?>">
                        <div class="grid-item-info">
                            <div class="grid-item-title"><?php echo htmlspecialchars($playlist['name']); ?></div>
                            <div class="grid-item-subtitle">By You</div>
                        </div>
                        <div class="grid-item-actions">
                            <button class="delete-playlist-btn" data-id="<?php echo $playlist['id']; ?>" title="Delete Playlist">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .action-btn {
            padding: 8px 16px;
            background-color: wheat;
            color: #121212;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }
        .action-btn:hover {
            background-color: #e6d9bc;
        }
        .empty-playlists {
            text-align: center;
            padding: 50px 0;
        }
        .empty-playlists i {
            font-size: 48px;
            color: #555;
            margin-bottom: 20px;
        }
        .empty-playlists p {
            font-size: 18px;
            color: #aaa;
            margin-bottom: 20px;
        }
        .create-playlist-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: wheat;
            color: #121212;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }
        .create-playlist-btn:hover {
            background-color: #e6d9bc;
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
        .grid-item {
            position: relative;
        }
        .grid-item-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: none;
        }
        .grid-item:hover .grid-item-actions {
            display: block;
        }
        .delete-playlist-btn {
            background-color: rgba(255, 107, 107, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .delete-playlist-btn:hover {
            background-color: rgba(255, 107, 107, 1);
        }
    </style>

    <script>
        // Delete playlist functionality
        const deleteButtons = document.querySelectorAll('.delete-playlist-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent triggering the grid item click

                if (confirm('Are you sure you want to delete this playlist?')) {
                    const playlistId = this.dataset.id;

                    // Send AJAX request to delete playlist
                    const formData = new FormData();
                    formData.append('action', 'delete_playlist');
                    formData.append('playlist_id', playlistId);

                    fetch('api.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Remove the playlist from the DOM
                                this.closest('.grid-item').remove();

                                // Show success message
                                const successMessage = document.createElement('div');
                                successMessage.className = 'success-message';
                                successMessage.textContent = data.message;

                                const sectionHeader = document.querySelector('.section-header');
                                sectionHeader.insertAdjacentElement('afterend', successMessage);

                                // Remove message after 3 seconds
                                setTimeout(() => {
                                    successMessage.remove();
                                }, 3000);

                                // If no playlists left, show empty state
                                if (document.querySelectorAll('.grid-item').length === 0) {
                                    const container = document.getElementById('user-playlists-container');
                                    container.innerHTML = `
                                <div class="empty-playlists">
                                    <i class="fas fa-list-ul"></i>
                                    <p>You don't have any playlists yet</p>
                                    <a href="create-playlist.php" class="create-playlist-btn">Create Your First Playlist</a>
                                </div>
                            `;
                                }
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the playlist');
                        });
                }
            });
        });
    </script>

<?php require_once 'includes/footer.php'; ?>