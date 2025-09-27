<?php
/** @var string $object */
/** @var int $id */
?>
<div class="ma-favourites">
    <button type="button" class="ma-favourite__toggle" aria-pressed="false" data-ma-favourite data-ma-favourite-type="<?php echo esc_attr( $object ); ?>" data-ma-favourite-id="<?php echo esc_attr( $id ); ?>">
        ♥
        <span class="screen-reader-text"><?php esc_html_e( 'Toggle favourite', 'music-archiver' ); ?></span>
    </button>
</div>
