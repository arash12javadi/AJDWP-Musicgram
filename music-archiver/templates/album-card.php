<?php
/**
 * @var array $albums
 * @var bool  $can_manage
 * @var bool  $show_login_prompt
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
            <?php foreach ( $albums as $album ) : ?>
                <article class="ma-album-card">
                    <h3 class="ma-album-card__title"><?php echo esc_html( $album['name'] ); ?></h3>
                    <?php if ( ! empty( $album['description'] ) ) : ?>
                        <p class="ma-album-card__description"><?php echo wp_kses_post( $album['description'] ); ?></p>
                    <?php endif; ?>
                    <button class="ma-album-card__play" data-ma-player-load="album:<?php echo esc_attr( $album['id'] ); ?>"><?php esc_html_e( 'Play Album', 'music-archiver' ); ?></button>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
