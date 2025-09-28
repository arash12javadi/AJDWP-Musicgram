<?php
/**
 * @var array $albums
 * @var bool  $can_manage
 * @var bool  $show_login_prompt
 * @var array $tracks_by_album
 */
    $unique_id       = wp_unique_id( 'ma-album-' );
    $name_id         = $unique_id . '-name';
    $description_id  = $unique_id . '-description';
    $public_id       = $unique_id . '-public';
?>
<div class="ma-album-card-wrapper" data-ma-album-wrapper data-ma-can-manage="<?php echo $can_manage ? '1' : '0'; ?>">
    <?php if ( $can_manage ) : ?>
        <form class="ma-album-card-form" data-ma-album-form>
            <fieldset>
                <legend class="ma-album-card-form__legend"><?php esc_html_e( 'Add a new album', 'music-archiver' ); ?></legend>
                <div class="ma-album-card-form__row">
                    <label for="<?php echo esc_attr( $name_id ); ?>" class="ma-album-card-form__label"><?php esc_html_e( 'Album name', 'music-archiver' ); ?></label>
                    <input type="text" id="<?php echo esc_attr( $name_id ); ?>" name="ma_album_name" class="ma-album-card-form__input" required maxlength="255" />
                </div>
                <div class="ma-album-card-form__row">
                    <label for="<?php echo esc_attr( $description_id ); ?>" class="ma-album-card-form__label"><?php esc_html_e( 'Description', 'music-archiver' ); ?></label>
                    <textarea id="<?php echo esc_attr( $description_id ); ?>" name="ma_album_description" class="ma-album-card-form__textarea" rows="3"></textarea>
                </div>
                <div class="ma-album-card-form__row ma-album-card-form__row--checkbox">
                    <label for="<?php echo esc_attr( $public_id ); ?>" class="ma-album-card-form__checkbox-label">
                        <input type="checkbox" id="<?php echo esc_attr( $public_id ); ?>" name="ma_album_public" value="1" />
                        <span><?php esc_html_e( 'Make album public', 'music-archiver' ); ?></span>
                    </label>
                </div>
                <div class="ma-album-card-form__actions">
                    <button type="submit" class="ma-album-card-form__submit"><?php esc_html_e( 'Create album', 'music-archiver' ); ?></button>
                </div>
                <p class="ma-album-card-form__feedback" data-ma-album-feedback aria-live="polite"></p>
            </fieldset>
        </form>
    <?php elseif ( $show_login_prompt ) : ?>
        <p class="ma-album-card__login">
            <?php esc_html_e( 'Log in to start building your album collection.', 'music-archiver' ); ?>
        </p>
    <?php endif; ?>

    <div class="ma-album-card-list" data-ma-album-list>
        <?php if ( empty( $albums ) ) : ?>
            <p class="ma-album-card__empty"><?php esc_html_e( 'No albums found.', 'music-archiver' ); ?></p>
        <?php else : ?>
            <?php foreach ( $albums as $album ) :
                $album_id      = (int) $album['id'];
                $album_tracks  = $tracks_by_album[ $album_id ] ?? [];
                $track_heading = sprintf( esc_html__( '%s tracks', 'music-archiver' ), number_format_i18n( count( $album_tracks ) ) );
            ?>
                <article class="ma-album-card" data-ma-album-id="<?php echo esc_attr( $album_id ); ?>">
                    <header class="ma-album-card__header">
                        <h3 class="ma-album-card__title"><?php echo esc_html( $album['name'] ); ?></h3>
                        <button type="button" class="ma-album-card__play" data-ma-player-load="album:<?php echo esc_attr( $album_id ); ?>"><?php esc_html_e( 'Play Album', 'music-archiver' ); ?></button>
                    </header>

                    <?php if ( ! empty( $album['description'] ) ) : ?>
                        <p class="ma-album-card__description"><?php echo wp_kses_post( $album['description'] ); ?></p>
                    <?php endif; ?>

                    <section class="ma-album-card__tracks" aria-label="<?php echo esc_attr( $track_heading ); ?>" data-ma-album-track-list>
                        <?php if ( empty( $album_tracks ) ) : ?>
                            <p class="ma-album-card__tracks-empty"><?php esc_html_e( 'No tracks yet.', 'music-archiver' ); ?></p>
                        <?php else : ?>
                            <ol class="ma-album-card__tracks-list">
                                <?php foreach ( $album_tracks as $track ) : ?>
                                    <li class="ma-album-card__tracks-item">
                                        <span class="ma-album-card__tracks-title"><?php echo esc_html( $track['title'] ); ?></span>
                                        <?php if ( ! empty( $track['source_url'] ) ) : ?>
                                            <a href="<?php echo esc_url( $track['source_url'] ); ?>" class="ma-album-card__tracks-link" target="_blank" rel="noopener">
                                                <?php esc_html_e( 'Open', 'music-archiver' ); ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </section>

                    <?php if ( $can_manage ) :
                        $track_unique   = wp_unique_id( 'ma-track-' );
                        $title_id       = $track_unique . '-title';
                        $url_id         = $track_unique . '-url';
                        $media_button_id = $track_unique . '-media';
                    ?>
                        <form class="ma-album-track-form" data-ma-album-track-form data-ma-album-id="<?php echo esc_attr( $album_id ); ?>">
                            <fieldset>
                                <legend class="ma-album-track-form__legend"><?php esc_html_e( 'Add a track', 'music-archiver' ); ?></legend>
                                <div class="ma-album-track-form__row">
                                    <label for="<?php echo esc_attr( $title_id ); ?>" class="ma-album-track-form__label"><?php esc_html_e( 'Track title', 'music-archiver' ); ?></label>
                                    <input type="text" id="<?php echo esc_attr( $title_id ); ?>" name="ma_track_title" class="ma-album-track-form__input" required maxlength="255" />
                                </div>
                                <div class="ma-album-track-form__row">
                                    <label for="<?php echo esc_attr( $url_id ); ?>" class="ma-album-track-form__label"><?php esc_html_e( 'Audio URL', 'music-archiver' ); ?></label>
                                    <input type="url" id="<?php echo esc_attr( $url_id ); ?>" name="ma_track_url" class="ma-album-track-form__input" placeholder="https://" />
                                    <p class="ma-album-track-form__help"><?php esc_html_e( 'Paste a direct audio link or upload a file below.', 'music-archiver' ); ?></p>
                                </div>
                                <div class="ma-album-track-form__row ma-album-track-form__row--media">
                                    <button type="button" id="<?php echo esc_attr( $media_button_id ); ?>" class="ma-album-track-form__media" data-ma-track-media><?php esc_html_e( 'Select or upload audio', 'music-archiver' ); ?></button>
                                    <input type="hidden" name="ma_track_attachment" value="" data-ma-track-attachment />
                                    <span class="ma-album-track-form__media-name" data-ma-track-media-name></span>
                                    <button type="button" class="ma-album-track-form__media-clear" data-ma-track-media-clear><?php esc_html_e( 'Clear', 'music-archiver' ); ?></button>
                                </div>
                                <div class="ma-album-track-form__row ma-album-track-form__actions">
                                    <button type="submit" class="ma-album-track-form__submit"><?php esc_html_e( 'Add track', 'music-archiver' ); ?></button>
                                </div>
                                <p class="ma-album-track-form__feedback" data-ma-track-feedback aria-live="polite"></p>
                            </fieldset>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

