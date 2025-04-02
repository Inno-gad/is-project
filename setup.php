<?php
// Database setup script
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS insync_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("insync_db");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('fan', 'artist') NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create artists table
$sql = "CREATE TABLE IF NOT EXISTS artists (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    artist_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Artists table created successfully<br>";
} else {
    echo "Error creating artists table: " . $conn->error . "<br>";
}

// Create songs table
$sql = "CREATE TABLE IF NOT EXISTS songs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    artist_id INT(11) NOT NULL,
    album VARCHAR(100),
    cover_image VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    duration INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Songs table created successfully<br>";
} else {
    echo "Error creating songs table: " . $conn->error . "<br>";
}

// Create playlists table
$sql = "CREATE TABLE IF NOT EXISTS playlists (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    user_id INT(11) NOT NULL,
    cover_image VARCHAR(255) DEFAULT 'placeholder.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Playlists table created successfully<br>";
} else {
    echo "Error creating playlists table: " . $conn->error . "<br>";
}

// Create playlist_songs table (junction table)
$sql = "CREATE TABLE IF NOT EXISTS playlist_songs (
    playlist_id INT(11) NOT NULL,
    song_id INT(11) NOT NULL,
    position INT(11) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (playlist_id, song_id),
    FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Playlist_songs table created successfully<br>";
} else {
    echo "Error creating playlist_songs table: " . $conn->error . "<br>";
}

// Create cart table
$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    song_id INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Cart table created successfully<br>";
} else {
    echo "Error creating cart table: " . $conn->error . "<br>";
}

// Create billing_info table
$sql = "CREATE TABLE IF NOT EXISTS billing_info (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    address VARCHAR(255),
    city VARCHAR(50),
    country VARCHAR(50),
    postal_code VARCHAR(20),
    card_number VARCHAR(255),
    card_expiry VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Billing_info table created successfully<br>";
} else {
    echo "Error creating billing_info table: " . $conn->error . "<br>";
}

// Insert sample artists data
$sql = "INSERT IGNORE INTO users (id, username, email, password, user_type, profile_picture) VALUES
(1, 'kaytranada', 'kaytranada@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'artist', 'Kaytranada.jpg'),
(2, 'esque', 'esque@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'artist', 'Esque.jpg'),
(3, 'daftpunk', 'daftpunk@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'artist', 'daft-punk.webp'),
(4, 'njerae', 'njerae@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'artist', 'Njerae.webp')";

if ($conn->query($sql) === TRUE) {
    echo "Sample users added successfully<br>";
} else {
    echo "Error adding sample users: " . $conn->error . "<br>";
}

$sql = "INSERT IGNORE INTO artists (id, user_id, artist_name, category) VALUES
(1, 1, 'KAYTRANADA', 'Producer'),
(2, 2, 'ESQUÉ', 'Producer'),
(3, 3, 'Daft Punk', 'Electronic'),
(4, 4, 'Njerae', 'R&B')";

if ($conn->query($sql) === TRUE) {
    echo "Sample artists added successfully<br>";
} else {
    echo "Error adding sample artists: " . $conn->error . "<br>";
}

echo "<p>Database setup complete! <a href='index.php'>Go to homepage</a></p>";

$conn->close();
?>