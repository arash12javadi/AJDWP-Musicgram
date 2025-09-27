<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Google_Drive {
    public static function init(): void {
        add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
        add_action( 'ma_drive_sync', [ self::class, 'cron_sync' ] );
    }

    public static function register_routes(): void {
        register_rest_route( 'ma/v1', '/drive/auth-url', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'rest_auth_url' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( 'ma/v1', '/drive/callback', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'rest_callback' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'ma/v1', '/drive/link', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'rest_link_album' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( 'ma/v1', '/drive/sync/(?P<album>\d+)', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'rest_sync_album' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );
    }

    public static function rest_auth_url( \WP_REST_Request $request ) {
        $settings = get_settings();
        if ( empty( $settings['gdrive_client_id'] ) ) {
            return rest_error( __( 'Google Drive is not configured.', 'music-archiver' ), 400 );
        }

        $state = wp_generate_password( 32, false );
        update_user_meta( get_current_user_id(), 'ma_drive_state', $state );

        $redirect_uri = urlencode( $settings['gdrive_redirect_uri'] );
        $url          = sprintf(
            'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&access_type=offline&prompt=consent&state=%s',
            rawurlencode( $settings['gdrive_client_id'] ),
            $redirect_uri,
            rawurlencode( 'https://www.googleapis.com/auth/drive.readonly' ),
            rawurlencode( $state )
        );

        return rest_success( [ 'url' => $url ] );
    }

    public static function rest_callback( \WP_REST_Request $request ) {
        $code  = sanitize_text_field( $request['code'] ?? '' );
        $state = sanitize_text_field( $request['state'] ?? '' );
        $user  = get_current_user_id();
        if ( ! $user ) {
            return rest_error( __( 'Authentication required.', 'music-archiver' ), 401 );
        }

        $expected = get_user_meta( $user, 'ma_drive_state', true );
        if ( ! $expected || $expected !== $state ) {
            return rest_error( __( 'Invalid OAuth state.', 'music-archiver' ), 400 );
        }

        $settings = get_settings();
        $body = [
            'code'          => $code,
            'client_id'     => $settings['gdrive_client_id'],
            'client_secret' => $settings['gdrive_client_secret'],
            'redirect_uri'  => $settings['gdrive_redirect_uri'],
            'grant_type'    => 'authorization_code',
        ];

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body'    => $body,
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            return rest_error( $response->get_error_message(), 400 );
        }

        $payload = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $payload['access_token'] ) ) {
            return rest_error( __( 'OAuth exchange failed.', 'music-archiver' ), 400 );
        }

        global $wpdb;
        $wpdb->replace(
            $wpdb->prefix . 'ma_drive_accounts',
            [
                'user_id'    => $user,
                'provider'   => 'gdrive',
                'label'      => sprintf( __( 'Google Drive (%s)', 'music-archiver' ), wp_get_current_user()->user_login ),
                'token_json' => encrypt_token( wp_json_encode( $payload ) ),
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s' ]
        );

        delete_user_meta( $user, 'ma_drive_state' );

        return rest_success( [ 'connected' => true ] );
    }

    public static function rest_link_album( \WP_REST_Request $request ) {
        $album_id = (int) $request->get_param( 'album_id' );
        $folder   = sanitize_text_field( $request->get_param( 'drive_folder_id' ) );
        if ( ! $album_id || ! $folder ) {
            return rest_error( __( 'Album and folder are required.', 'music-archiver' ) );
        }

        global $wpdb;
        $wpdb->replace( $wpdb->prefix . 'ma_drive_links', [
            'album_id'       => $album_id,
            'drive_folder_id'=> $folder,
            'last_synced_at' => current_time( 'mysql' ),
        ], [ '%d', '%s', '%s' ] );

        return rest_success( [ 'linked' => true ] );
    }

    public static function rest_sync_album( \WP_REST_Request $request ) {
        $album_id = (int) $request['album'];
        self::sync_album_tracks( $album_id );
        return rest_success( [ 'synced' => true ] );
    }

    public static function cron_sync(): void {
        global $wpdb;
        $albums = $wpdb->get_col( "SELECT album_id FROM {$wpdb->prefix}ma_drive_links" );
        foreach ( $albums as $album_id ) {
            self::sync_album_tracks( (int) $album_id );
        }
    }

    private static function sync_album_tracks( int $album_id ): void {
        if ( ! $album_id ) {
            return;
        }

        global $wpdb;
        $link = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_drive_links WHERE album_id = %d", $album_id ) );
        if ( ! $link ) {
            return;
        }

        $album = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_albums WHERE id = %d", $album_id ) );
        if ( ! $album ) {
            return;
        }

        $account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_drive_accounts WHERE user_id = %d", $album->user_id ) );
        if ( ! $account ) {
            return;
        }

        $token = decrypt_token( $account->token_json );
        if ( ! $token ) {
            return;
        }

        $token = json_decode( $token, true );
        $access_token = $token['access_token'] ?? '';
        if ( ! $access_token ) {
            return;
        }

        $url = sprintf( 'https://www.googleapis.com/drive/v3/files?q=%s&fields=files(id,name,mimeType,webContentLink)', rawurlencode( sprintf( "'%s' in parents and mimeType contains 'audio/'", $link->drive_folder_id ) ) );
        $response = wp_remote_get( $url, [ 'headers' => [ 'Authorization' => 'Bearer ' . $access_token ] ] );
        if ( is_wp_error( $response ) ) {
            return;
        }
        $payload = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $payload['files'] ) ) {
            return;
        }

        foreach ( $payload['files'] as $index => $file ) {
            if ( empty( $file['webContentLink'] ) ) {
                continue;
            }

            $wpdb->replace( $wpdb->prefix . 'ma_tracks', [
                'user_id'      => $album->user_id,
                'title'        => sanitize_text_field( $file['name'] ),
                'source_url'   => esc_url_raw( $file['webContentLink'] ),
                'attachment_id'=> 0,
                'duration_sec' => null,
                'created_at'   => current_time( 'mysql' ),
            ], [ '%d', '%s', '%s', '%d', '%d', '%s' ] );
        }

        $wpdb->update( $wpdb->prefix . 'ma_drive_links', [ 'last_synced_at' => current_time( 'mysql' ) ], [ 'album_id' => $album_id ], [ '%s' ], [ '%d' ] );
    }
}
