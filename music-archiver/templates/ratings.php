<?php
/** @var string $object */
/** @var int $id */
?>
<div class="ma-ratings" data-ma-rating-object="<?php echo esc_attr( $object ); ?>" data-ma-rating-id="<?php echo esc_attr( $id ); ?>">
    <div class="ma-rating__stars" role="group" aria-label="<?php esc_attr_e( 'Rate this item', 'music-archiver' ); ?>">
        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
            <button type="button" data-ma-rating="<?php echo esc_attr( $i ); ?>" aria-pressed="false">
                <span class="screen-reader-text"><?php echo esc_html( sprintf( __( '%d star', 'music-archiver' ), $i ) ); ?></span>
                ★
            </button>
        <?php endfor; ?>
    </div>
</div>
