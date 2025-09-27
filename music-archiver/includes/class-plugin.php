<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Plugin {
    public function init(): void {
        require_once MA_PATH . 'includes/class-db.php';
        require_once MA_PATH . 'includes/class-roles.php';
        require_once MA_PATH . 'includes/class-cpt.php';
        require_once MA_PATH . 'includes/class-rest.php';
        require_once MA_PATH . 'includes/class-settings.php';
        require_once MA_PATH . 'includes/class-admin-ui.php';
        require_once MA_PATH . 'includes/class-player.php';
        require_once MA_PATH . 'includes/class-google-drive.php';
        require_once MA_PATH . 'includes/class-premium.php';

        add_action( 'init', [ DB::class, 'maybe_create_tables' ] );
        add_action( 'init', [ Roles::class, 'register_roles' ] );
        add_action( 'init', [ CPT::class, 'register' ] );
        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'init', [ Player::class, 'register_shortcodes' ] );
        add_action( 'init', [ Player::class, 'register_assets' ] );

        add_action( 'admin_init', [ Settings::class, 'register' ] );
        add_action( 'admin_menu', [ Admin_UI::class, 'register_menu' ] );

        add_action( 'rest_api_init', [ Rest::class, 'register_routes' ] );

        add_action( 'plugins_loaded', [ Premium::class, 'init' ] );
        add_action( 'plugins_loaded', [ Google_Drive::class, 'init' ] );

        add_action( 'enqueue_block_editor_assets', [ Player::class, 'enqueue_block_editor_assets' ] );
        add_action( 'wp_enqueue_scripts', [ Player::class, 'enqueue_public_assets' ] );

        add_action( 'init', [ $this, 'register_privacy_exporters' ] );
    }

    public function register_blocks(): void {
        if ( function_exists( 'register_block_type' ) ) {
            register_block_type( MA_PATH . 'blocks/album-list' );
            register_block_type( MA_PATH . 'blocks/playlist' );
            register_block_type( MA_PATH . 'blocks/player' );
        }
    }

    public function register_privacy_exporters(): void {
        if ( ! function_exists( 'wp_register_personal_data_exporter' ) ) {
            return;
        }

        $callback = [ $this, 'privacy_exporter' ];
        $eraser   = [ $this, 'privacy_eraser' ];

        wp_register_personal_data_exporter( 'music-archiver', __( 'Music Archiver Data', 'music-archiver' ), $callback );
        wp_register_personal_data_eraser( 'music-archiver', __( 'Music Archiver Data', 'music-archiver' ), $eraser );
    }

    public function privacy_exporter( string $email, int $page = 1 ): array {
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            return [ 'data' => [], 'done' => true ];
        }

        global $wpdb;
        $tables = [
            'albums'      => $wpdb->prefix . 'ma_albums',
            'tracks'      => $wpdb->prefix . 'ma_tracks',
            'playlists'   => $wpdb->prefix . 'ma_playlists',
            'ratings'     => $wpdb->prefix . 'ma_ratings',
            'favourites'  => $wpdb->prefix . 'ma_favourites',
            'drive_links' => $wpdb->prefix . 'ma_drive_links',
        ];

        $data = [];
        foreach ( $tables as $label => $table ) {
            $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user->ID ), ARRAY_A );
            if ( ! empty( $items ) ) {
                $data[] = [
                    'group_id'    => "ma_{$label}",
                    'group_label' => ucfirst( $label ),
                    'item_id'     => "ma_{$label}_{$user->ID}",
                    'data'        => array_map( static fn( $item ) => [
                        'name'  => sprintf( __( '%s data', 'music-archiver' ), ucfirst( $label ) ),
                        'value' => wp_json_encode( $item ),
                    ], $items ),
                ];
            }
        }

        return [ 'data' => $data, 'done' => true ];
    }

    public function privacy_eraser( string $email, int $page = 1 ): array {
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            return [ 'items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true ];
        }

        global $wpdb;
        $tables = [
            $wpdb->prefix . 'ma_albums',
            $wpdb->prefix . 'ma_tracks',
            $wpdb->prefix . 'ma_playlists',
            $wpdb->prefix . 'ma_playlist_items',
            $wpdb->prefix . 'ma_ratings',
            $wpdb->prefix . 'ma_favourites',
            $wpdb->prefix . 'ma_drive_accounts',
            $wpdb->prefix . 'ma_drive_links',
        ];

        foreach ( $tables as $table ) {
            $wpdb->delete( $table, [ 'user_id' => $user->ID ], [ '%d' ] );
        }

        delete_user_meta( $user->ID, 'ma_is_premium' );

        return [ 'items_removed' => true, 'items_retained' => false, 'messages' => [], 'done' => true ];
    }
}
