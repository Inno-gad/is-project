<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Like/unlike a song
    if ($action === 'toggle_like') {
        $song_id = isset($_POST['song_id']) ? $_POST['song_id'] : 0;

        if ($song_id > 0) {
            // Check if already liked
            if (hasLikedSong($conn, $user_id, $song_id)) {
                // Unlike
                $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND song_id = ?");
                $stmt->bind_param("ii", $user_id, $song_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'liked' => false, 'count' => getSongLikes($conn, $song_id)];
                } else {
                    $response = ['success' => false, 'message' => 'Error unliking song'];
                }
            } else {
                // Like
                $stmt = $conn->prepare("INSERT INTO likes (user_id, song_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $user_id, $song_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'liked' => true, 'count' => getSongLikes($conn, $song_id)];
                } else {
                    $response = ['success' => false, 'message' => 'Error liking song'];
                }
            }
        }
    }

    // Follow/unfollow an artist
    elseif ($action === 'toggle_follow') {
        $artist_id = isset($_POST['artist_id']) ? $_POST['artist_id'] : 0;

        if ($artist_id > 0) {
            // Check if already following
            if (followsArtist($conn, $user_id, $artist_id)) {
                // Unfollow
                $stmt = $conn->prepare("DELETE FROM follows WHERE user_id = ? AND artist_id = ?");
                $stmt->bind_param("ii", $user_id, $artist_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'following' => false, 'count' => getArtistFollowers($conn, $artist_id)];
                } else {
                    $response = ['success' => false, 'message' => 'Error unfollowing artist'];
                }
            } else {
                // Follow
                $stmt = $conn->prepare("INSERT INTO follows (user_id, artist_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $user_id, $artist_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'following' => true, 'count' => getArtistFollowers($conn, $artist_id)];
                } else {
                    $response = ['success' => false, 'message' => 'Error following artist'];
                }
            }
        }
    }

    // Share a song
    elseif ($action === 'share_song') {
        $song_id = isset($_POST['song_id']) ? $_POST['song_id'] : 0;
        $share_method = isset($_POST['share_method']) ? $_POST['share_method'] : '';

        if ($song_id > 0 && !empty($share_method)) {
            $stmt = $conn->prepare("INSERT INTO shares (user_id, song_id, shared_to) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $song_id, $share_method);

            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Song shared successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Error sharing song'];
            }
        }
    }

    // Add to cart (song or playlist)
    elseif ($action === 'add_to_cart') {
        // Check if it's a song
        if (isset($_POST['song_id'])) {
            $song_id = intval($_POST['song_id']);
            $price = floatval($_POST['price']);

            if ($song_id > 0 && $price > 0) {
                // Check if already purchased
                $stmt = $conn->prepare("SELECT id FROM purchases WHERE user_id = ? AND song_id = ?");
                $stmt->bind_param("ii", $user_id, $song_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $response = ['success' => false, 'message' => 'You already own this song'];
                } else {
                    // Check if already in cart
                    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND song_id = ?");
                    $stmt->bind_param("ii", $user_id, $song_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $response = ['success' => false, 'message' => 'Song already in cart'];
                    } else {
                        // Add to cart
                        $stmt = $conn->prepare("INSERT INTO cart (user_id, song_id, price) VALUES (?, ?, ?)");
                        $stmt->bind_param("iid", $user_id, $song_id, $price);

                        if ($stmt->execute()) {
                            $cart_count = getCartCount($conn, $user_id);
                            $response = ['success' => true, 'message' => 'Song added to cart', 'cart_count' => $cart_count];
                        } else {
                            $response = ['success' => false, 'message' => 'Error adding song to cart: ' . $conn->error];
                        }
                    }
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid song ID or price'];
            }
        }
        // Handle playlist purchase
        elseif (isset($_POST['playlist_id'])) {
            $playlist_id = intval($_POST['playlist_id']);
            $price = floatval($_POST['price']);

            if ($playlist_id > 0 && $price > 0) {
                // Check if already purchased
                $stmt = $conn->prepare("SELECT id FROM playlist_purchases WHERE user_id = ? AND playlist_id = ?");
                $stmt->bind_param("ii", $user_id, $playlist_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $response = ['success' => false, 'message' => 'You already own this playlist'];
                } else {
                    // Check if already in cart
                    $stmt = $conn->prepare("SELECT id FROM playlist_cart WHERE user_id = ? AND playlist_id = ?");
                    $stmt->bind_param("ii", $user_id, $playlist_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $response = ['success' => false, 'message' => 'Playlist already in cart'];
                    } else {
                        // Add to cart
                        $stmt = $conn->prepare("INSERT INTO playlist_cart (user_id, playlist_id, price) VALUES (?, ?, ?)");
                        $stmt->bind_param("iid", $user_id, $playlist_id, $price);

                        if ($stmt->execute()) {
                            $cart_count = getCartCount($conn, $user_id);
                            $response = ['success' => true, 'message' => 'Playlist added to cart', 'cart_count' => $cart_count];
                        } else {
                            $response = ['success' => false, 'message' => 'Error adding playlist to cart: ' . $conn->error];
                        }
                    }
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid playlist ID or price'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Missing song_id or playlist_id parameter'];
        }
    }

    // Delete playlist
    elseif ($action === 'delete_playlist') {
        $playlist_id = isset($_POST['playlist_id']) ? $_POST['playlist_id'] : 0;

        if ($playlist_id > 0) {
            // Verify ownership
            $stmt = $conn->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $playlist_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Delete playlist
                $stmt = $conn->prepare("DELETE FROM playlists WHERE id = ?");
                $stmt->bind_param("i", $playlist_id);

                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Playlist deleted successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Error deleting playlist'];
                }
            } else {
                $response = ['success' => false, 'message' => 'You do not have permission to delete this playlist'];
            }
        }
    }
}

echo json_encode($response);
?>