<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class CPT {
    public static function register(): void {
        register_post_type( 'ma_album', [
            'labels' => [
                'name'          => __( 'Albums', 'music-archiver' ),
                'singular_name' => __( 'Album', 'music-archiver' ),
            ],
            'public'       => true,
            'show_in_menu' => false,
            'rewrite'      => [ 'slug' => 'album' ],
            'supports'     => [ 'title', 'editor', 'thumbnail', 'author' ],
        ] );

        register_post_type( 'ma_track', [
            'labels' => [
                'name'          => __( 'Tracks', 'music-archiver' ),
                'singular_name' => __( 'Track', 'music-archiver' ),
            ],
            'public'       => true,
            'show_in_menu' => false,
            'rewrite'      => [ 'slug' => 'track' ],
            'supports'     => [ 'title', 'editor', 'thumbnail', 'author' ],
        ] );
    }
}
