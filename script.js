// DOM Elements
const artistContainer = document.getElementById("artist-container");
const playlistContainer = document.getElementById("playlist-container");
const searchInput = document.getElementById("search-input");
const playPauseButton = document.getElementById("play-pause-button");
const prevButton = document.getElementById("prev-button");
const nextButton = document.getElementById("next-button");
const shuffleButton = document.getElementById("shuffle-button");
const repeatButton = document.getElementById("repeat-button");
const progressBar = document.querySelector(".progress");
const currentTimeEl = document.getElementById("current-time");
const totalTimeEl = document.getElementById("total-time");
const volumeSlider = document.getElementById("volume-slider");

let currentSongIndex = 0;
let isPlaying = false;
let shuffle = false;
let repeat = false;

// Functions
function filterItems(items, searchTerm) {
  return items.filter(
    (item) =>
      item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (item.category && item.category.toLowerCase().includes(searchTerm.toLowerCase())) ||
      (item.artist_name && item.artist_name.toLowerCase().includes(searchTerm.toLowerCase())) ||
      (item.author && item.author.toLowerCase().includes(searchTerm.toLowerCase())),
  );
}

function updateNowPlaying(song) {
  if (!song) return;
  
  document.getElementById("current-song-cover").src = `uploads/covers/${song.cover_image}`;
  document.getElementById("current-song-title").textContent = song.title;
  document.getElementById("current-song-artist").textContent = song.artist_name || "Unknown Artist";
  
  if (song.duration) {
    totalTimeEl.textContent = formatTime(song.duration);
  } else {
    totalTimeEl.textContent = "0:00";
  }
  
  // Reset progress
  currentTimeEl.textContent = "0:00";
  progressBar.style.width = "0%";
}

function formatTime(seconds) {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes}:${remainingSeconds.toString().padStart(2, "0")}`;
}

function togglePlayPause() {
  isPlaying = !isPlaying;
  playPauseButton.innerHTML = isPlaying ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>';
}

function nextSong() {
  // In a real app, this would play the next song
  togglePlayPause();
}

function prevSong() {
  // In a real app, this would play the previous song
  togglePlayPause();
}

function toggleShuffle() {
  shuffle = !shuffle;
  shuffleButton.classList.toggle("active");
}

function toggleRepeat() {
  repeat = !repeat;
  repeatButton.classList.toggle("active");
}

// Event Listeners
if (searchInput) {
  searchInput.addEventListener("input", () => {
    const searchTerm = searchInput.value;
    
    // This would typically make an AJAX request to search the database
    console.log(`Searching for: ${searchTerm}`);
  });
}

if (playPauseButton) playPauseButton.addEventListener("click", togglePlayPause);
if (prevButton) prevButton.addEventListener("click", prevSong);
if (nextButton) nextButton.addEventListener("click", nextSong);
if (shuffleButton) shuffleButton.addEventListener("click", toggleShuffle);
if (repeatButton) repeatButton.addEventListener("click", toggleRepeat);

if (volumeSlider) {
  volumeSlider.addEventListener("input", () => {
    // Adjust volume logic here
    console.log(`Volume set to ${volumeSlider.value}%`);
  });
}

// Simulated song progress
setInterval(() => {
  if (isPlaying) {
    const currentTime =
      Number.parseInt(currentTimeEl.textContent.split(":")[0]) * 60 +
      Number.parseInt(currentTimeEl.textContent.split(":")[1]);
    
    const totalTime = 
      Number.parseInt(totalTimeEl.textContent.split(":")[0]) * 60 +
      Number.parseInt(totalTimeEl.textContent.split(":")[1] || "0");
    
    if (currentTime < totalTime) {
      const newTime = currentTime + 1;
      currentTimeEl.textContent = formatTime(newTime);
      progressBar.style.width = `${(newTime / totalTime) * 100}%`;
    } else if (repeat) {
      currentTimeEl.textContent = "0:00";
      progressBar.style.width = "0%";
    } else {
      nextSong();
    }
  }
}, 1000);

// Initialize grid items click events
document.addEventListener('DOMContentLoaded', function() {
  // Add click events to grid items
  const gridItems = document.querySelectorAll('.grid-item');
  gridItems.forEach(item => {
    item.addEventListener('click', function() {
      const id = this.dataset.id;
      const type = this.closest('.grid-container').id.includes('artist') ? 'artist' : 'playlist';
      
      console.log(`Clicked ${type} with ID: ${id}`);
      // In a real app, this would navigate to the artist or playlist page
    });
  });
});