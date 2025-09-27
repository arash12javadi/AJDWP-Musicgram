<?php
/** @var string $source */
?>
<div class="ma-player" data-ma-player>
    <div class="ma-player__now-playing">
        <strong><?php esc_html_e( 'Now Playing:', 'music-archiver' ); ?></strong>
        <span data-ma-player-title><?php esc_html_e( 'Select a track to start playback.', 'music-archiver' ); ?></span>
    </div>
    <audio controls preload="none" <?php echo $source ? 'data-ma-player-source="' . esc_attr( $source ) . '"' : ''; ?>></audio>
</div>
