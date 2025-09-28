<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Player {
    public static function register_shortcodes(): void {
        add_shortcode( 'ma_album_list', [ self::class, 'shortcode_album_list' ] );
        add_shortcode( 'ma_playlist', [ self::class, 'shortcode_playlist' ] );
        add_shortcode( 'ma_player', [ self::class, 'shortcode_player' ] );
        add_shortcode( 'ma_ratings', [ self::class, 'shortcode_ratings' ] );
        add_shortcode( 'ma_favourites', [ self::class, 'shortcode_favourites' ] );
    }

    public static function register_assets(): void {
        wp_register_style( 'music-archiver', MA_URL . 'public/css/music-archiver.css', [], MA_VERSION );
        wp_register_script( 'ma-player', MA_URL . 'public/js/player.js', [ 'wp-api-fetch' ], MA_VERSION, true );
        wp_register_script( 'ma-rating', MA_URL . 'public/js/rating.js', [ 'wp-api-fetch' ], MA_VERSION, true );
        wp_register_script( 'ma-favourite', MA_URL . 'public/js/favourite.js', [ 'wp-api-fetch' ], MA_VERSION, true );
        wp_register_script( 'ma-playlist', MA_URL . 'public/js/playlist.js', [ 'wp-api-fetch' ], MA_VERSION, true );
        wp_register_script( 'ma-search', MA_URL . 'public/js/search.js', [ 'wp-api-fetch' ], MA_VERSION, true );
        wp_register_script( 'ma-albums', MA_URL . 'public/js/albums.js', [ 'wp-api-fetch', 'wp-i18n' ], MA_VERSION, true );

        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'ma-albums', 'music-archiver', MA_PATH . 'languages' );
        }

        wp_register_script( 'music-archiver-album-list-editor', MA_URL . 'blocks/album-list/edit.js', [ 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor' ], MA_VERSION, true );
        wp_register_script( 'music-archiver-playlist-editor', MA_URL . 'blocks/playlist/edit.js', [ 'wp-blocks', 'wp-element', 'wp-i18n' ], MA_VERSION, true );
        wp_register_script( 'music-archiver-player-editor', MA_URL . 'blocks/player/edit.js', [ 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor' ], MA_VERSION, true );
    }

    public static function enqueue_public_assets(): void {
        $post    = get_post();
        $content = $post ? $post->post_content : '';

        $shortcodes = [ 'ma_player', 'ma_album_list', 'ma_playlist', 'ma_ratings', 'ma_favourites' ];
        $has_shortcode = false;
        if ( $content ) {
            foreach ( $shortcodes as $shortcode ) {
                if ( has_shortcode( $content, $shortcode ) ) {
                    $has_shortcode = true;
                    break;
                }
            }
        }

        $blocks      = [ 'music-archiver/player', 'music-archiver/playlist', 'music-archiver/album-list' ];
        $has_block   = false;
        if ( function_exists( 'has_block' ) && $post ) {
            foreach ( $blocks as $block ) {
                if ( has_block( $block, $post ) ) {
                    $has_block = true;
                    break;
                }
            }
        }

        if ( ! $has_shortcode && ! $has_block ) {
            return;
        }

        wp_enqueue_style( 'music-archiver' );
        wp_enqueue_script( 'ma-player' );
        wp_enqueue_script( 'ma-rating' );
        wp_enqueue_script( 'ma-favourite' );
        wp_enqueue_script( 'ma-playlist' );
        wp_enqueue_script( 'ma-search' );
        wp_enqueue_script( 'ma-albums' );

        wp_localize_script( 'ma-player', 'MAPlayer', [
            'restUrl' => esc_url_raw( rest_url( 'ma/v1/' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'autoplay'=> (bool) get_settings()['player_autoplay'],
            'shuffle' => (bool) get_settings()['player_shuffle'],
        ] );
    }

    public static function enqueue_block_editor_assets(): void {
        wp_enqueue_style( 'music-archiver' );
    }

    public static function shortcode_album_list( $atts ): string {
        $atts = shortcode_atts( [
            'user'   => 'me',
            'public' => '0',
        ], $atts, 'ma_album_list' );

        ob_start();
        $template = MA_PATH . 'templates/album-card.php';
        if ( file_exists( $template ) ) {
            $requested_self    = 'me' === $atts['user'];
            $user_id           = $requested_self ? get_current_user_id() : (int) $atts['user'];
            $public            = (int) $atts['public'];
            $current_user      = get_current_user_id();
            $can_manage        = is_user_logged_in() && ( $requested_self || (int) $user_id === $current_user || current_user_can( 'ma_manage' ) );
            $show_login_prompt = ! is_user_logged_in() && $requested_self;

            global $wpdb;
            $query = "SELECT * FROM {$wpdb->prefix}ma_albums WHERE user_id = %d";
            if ( $public || ( ! $can_manage && $user_id ) ) {
                $query .= ' AND is_public = 1';
            }
            $albums = $wpdb->get_results( $wpdb->prepare( $query, $user_id ), ARRAY_A );

            $tracks_by_album = [];
            if ( $albums ) {
                $album_ids = array_filter( array_map( 'absint', wp_list_pluck( $albums, 'id' ) ) );
                if ( $album_ids ) {
                    $in = implode( ',', $album_ids );
                    $rows = $wpdb->get_results( "SELECT at.album_id, at.id AS album_track_id, at.position, t.* FROM {$wpdb->prefix}ma_album_track at JOIN {$wpdb->prefix}ma_tracks t ON t.id = at.track_id WHERE at.album_id IN ( {$in} ) ORDER BY at.album_id, at.position", ARRAY_A );
                    foreach ( $rows as $row ) {
                        $album_id_key = (int) $row['album_id'];
                        if ( ! isset( $tracks_by_album[ $album_id_key ] ) ) {
                            $tracks_by_album[ $album_id_key ] = [];
                        }
                        $tracks_by_album[ $album_id_key ][] = $row;
                    }
                }
            }

            if ( $can_manage ) {
                self::ensure_media_enqueued();
            }

            include $template;
        }

        return ob_get_clean();
    }

    public static function shortcode_playlist(): string {
        ob_start();
        $template = MA_PATH . 'templates/playlist.php';
        if ( file_exists( $template ) ) {
            include $template;
        }
        return ob_get_clean();
    }

    public static function shortcode_player( $atts ): string {
        $atts = shortcode_atts( [ 'source' => '' ], $atts, 'ma_player' );
        ob_start();
        $template = MA_PATH . 'templates/player.php';
        if ( file_exists( $template ) ) {
            $source = $atts['source'];
            include $template;
        }
        return ob_get_clean();
    }

    public static function shortcode_ratings( $atts ): string {
        $atts = shortcode_atts( [ 'object' => 'album', 'id' => 0 ], $atts, 'ma_ratings' );
        ob_start();
        $template = MA_PATH . 'templates/ratings.php';
        if ( file_exists( $template ) ) {
            $object = $atts['object'];
            $id     = (int) $atts['id'];
            include $template;
        }
        return ob_get_clean();
    }

    public static function shortcode_favourites( $atts ): string {
        $atts = shortcode_atts( [ 'object' => 'album', 'id' => 0 ], $atts, 'ma_favourites' );
        ob_start();
        $template = MA_PATH . 'templates/favourites.php';
        if ( file_exists( $template ) ) {
            $object = $atts['object'];
            $id     = (int) $atts['id'];
            include $template;
        }
        return ob_get_clean();
    }

    private static function ensure_media_enqueued(): void {
        static $enqueued = false;
        if ( $enqueued ) {
            return;
        }

        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }

        $enqueued = true;
    }
}

