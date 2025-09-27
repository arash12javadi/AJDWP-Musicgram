<?php
/** @var array $track */
?>
<div class="ma-track-row" data-id="<?php echo esc_attr( $track['id'] ?? 0 ); ?>">
    <span class="ma-track-row__title"><?php echo esc_html( $track['title'] ?? '' ); ?></span>
    <div class="ma-track-row__actions">
        <button type="button" class="ma-track-row__play" data-ma-player-load="track:<?php echo esc_attr( $track['id'] ?? 0 ); ?>">
            <?php esc_html_e( 'Play', 'music-archiver' ); ?>
        </button>
        <button type="button" class="ma-track-row__playlist" data-ma-playlist-add="<?php echo esc_attr( $track['id'] ?? 0 ); ?>">
            <?php esc_html_e( 'Add to playlist', 'music-archiver' ); ?>
        </button>
    </div>
</div>
