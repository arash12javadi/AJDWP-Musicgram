<?php
/** @var array $albums */
if ( empty( $albums ) ) :
    echo '<p class="ma-album-card__empty">' . esc_html__( 'No albums found.', 'music-archiver' ) . '</p>';
else :
    echo '<div class="ma-album-card-list">';
    foreach ( $albums as $album ) {
        echo '<article class="ma-album-card">';
        echo '<h3 class="ma-album-card__title">' . esc_html( $album['name'] ) . '</h3>';
        if ( ! empty( $album['description'] ) ) {
            echo '<p class="ma-album-card__description">' . wp_kses_post( $album['description'] ) . '</p>';
        }
        echo '<button class="ma-album-card__play" data-ma-player-load="album:' . esc_attr( $album['id'] ) . '">' . esc_html__( 'Play Album', 'music-archiver' ) . '</button>';
        echo '</article>';
    }
    echo '</div>';
endif;
