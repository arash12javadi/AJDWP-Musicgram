<?php
/**
 * @var array $tracks
 * @var bool  $show_login_prompt
 */
?>
<div class="ma-track-grid" data-ma-track-wrapper>
    <?php if ( $show_login_prompt ) : ?>
        <p class="ma-track-grid__login"><?php esc_html_e( 'Log in to browse your tracks.', 'music-archiver' ); ?></p>
    <?php endif; ?>

    <?php if ( empty( $tracks ) ) : ?>
        <p class="ma-track-grid__empty"><?php esc_html_e( 'No tracks available yet.', 'music-archiver' ); ?></p>
    <?php else : ?>
        <div class="ma-track-grid__list">
            <?php foreach ( $tracks as $track ) :
                $track_id   = (int) $track['id'];
                $title      = ! empty( $track['title'] ) ? $track['title'] : __( 'Untitled track', 'music-archiver' );
                $source_url = ! empty( $track['source_url'] ) ? $track['source_url'] : '';
                $can_play   = ! empty( $source_url );
            ?>
                <article class="ma-track-card" data-ma-track-id="<?php echo esc_attr( $track_id ); ?>">
                    <header class="ma-track-card__header">
                        <h4 class="ma-track-card__title"><?php echo esc_html( $title ); ?></h4>
                        <button type="button" class="ma-track-card__play" <?php echo $can_play ? 'data-ma-player-load="track:' . esc_attr( $track_id ) . '"' : 'disabled'; ?>>
                            <?php esc_html_e( 'Play', 'music-archiver' ); ?>
                        </button>
                    </header>

                    <div class="ma-track-card__actions">
                        <?php if ( $track_id ) : ?>
                            <button type="button" class="ma-track-card__add" data-ma-playlist-add="<?php echo esc_attr( $track_id ); ?>">
                                <?php esc_html_e( 'Add to playlist', 'music-archiver' ); ?>
                            </button>
                        <?php else : ?>
                            <button type="button" class="ma-track-card__add" disabled>
                                <?php esc_html_e( 'Add to playlist', 'music-archiver' ); ?>
                            </button>
                        <?php endif; ?>
                        <?php if ( $source_url ) : ?>
                            <a class="ma-track-card__open" href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener">
                                <?php esc_html_e( 'Open original', 'music-archiver' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>