<?php
?>
<div class="ma-playlist" data-ma-playlist>
    <header class="ma-playlist__header">
        <h3 class="ma-playlist__title" data-ma-playlist-title><?php esc_html_e( 'My Playlist', 'music-archiver' ); ?></h3>
        <button type="button" class="ma-playlist__play-all" data-ma-playlist-play disabled>
            <?php esc_html_e( 'Play all', 'music-archiver' ); ?>
        </button>
    </header>
    <p class="ma-playlist__empty" data-ma-playlist-empty><?php esc_html_e( 'No tracks in your playlist yet.', 'music-archiver' ); ?></p>
    <ul class="ma-playlist__items" data-ma-playlist-sortable></ul>
</div>

