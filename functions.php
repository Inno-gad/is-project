<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is an artist
function isArtist() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'artist';
}

// Get user data
function getUserData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get artist data
function getArtistData($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM artists WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Format time
function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return $minutes . ':' . str_pad($remainingSeconds, 2, '0', STR_PAD_LEFT);
}

// Get cart items count
function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get cart total
function getCartTotal($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(price) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ? $row['total'] : 0;
}

// Check if user has liked a song
function hasLikedSong($conn, $user_id, $song_id) {
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $user_id, $song_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Check if user follows an artist
function followsArtist($conn, $user_id, $artist_id) {
    $stmt = $conn->prepare("SELECT id FROM follows WHERE user_id = ? AND artist_id = ?");
    $stmt->bind_param("ii", $user_id, $artist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get like count for a song
function getSongLikes($conn, $song_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE song_id = ?");
    $stmt->bind_param("i", $song_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get follower count for an artist
function getArtistFollowers($conn, $artist_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE artist_id = ?");
    $stmt->bind_param("i", $artist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Check if user has purchased a song
function hasPurchasedSong($conn, $user_id, $song_id) {
    $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $user_id, $song_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
?>