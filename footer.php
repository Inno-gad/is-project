        </div><!-- End content-area -->
    </div><!-- End main-content -->

    <div class="now-playing-container" id="now-playing">
        <div class="song-info">
            <img src="assets/images/placeholder.jpg" alt="Album Cover" id="current-song-cover">
            <div>
                <h3 id="current-song-title">Song Title</h3>
                <p id="current-song-artist">Artist Name</p>
            </div>
        </div>
        <div class="player-controls">
            <button id="shuffle-button"><i class="fas fa-random"></i></button>
            <button id="prev-button"><i class="fas fa-step-backward"></i></button>
            <button id="play-pause-button"><i class="fas fa-play"></i></button>
            <button id="next-button"><i class="fas fa-step-forward"></i></button>
            <button id="repeat-button"><i class="fas fa-redo"></i></button>
        </div>
        <div class="progress-container">
            <span id="current-time">0:00</span>
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
            <span id="total-time">0:00</span>
        </div>
        <div class="volume-control">
            <i class="fas fa-volume-up"></i>
            <input type="range" id="volume-slider" min="0" max="100" value="100">
        </div>
    </div>
</div>

<script src="assets/js/script.js"></script>
</body>
</html>