<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Rest {
    public static function register_routes(): void {
        $namespace = 'ma/v1';

        register_rest_route( $namespace, '/albums', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_albums' ],
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_album' ],
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ],
        ] );

        register_rest_route( $namespace, '/albums/(?P<id>\d+)', [
            'methods'             => [ 'GET', 'PATCH', 'DELETE' ],
            'callback'            => [ self::class, 'album_detail' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
            'args' => [
                'id' => [ 'validate_callback' => 'is_numeric' ],
            ],
        ] );

        register_rest_route( $namespace, '/albums/(?P<id>\d+)/tracks', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_album_tracks' ],
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'add_album_track' ],
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ],
        ] );

        register_rest_route( $namespace, '/tracks', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_tracks' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/albums/(?P<id>\d+)/order', [
            'methods'             => 'PATCH',
            'callback'            => [ self::class, 'update_album_order' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/tracks/(?P<id>\d+)', [
            'methods'             => [ 'GET', 'DELETE' ],
            'callback'            => [ self::class, 'track_detail' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/ratings', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'submit_rating' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/favourites/toggle', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'toggle_favourite' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/playlist', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_playlist' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/playlist/items', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'add_playlist_item' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/playlist/order', [
            'methods'             => 'PATCH',
            'callback'            => [ self::class, 'update_playlist_order' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/playlist/items/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ self::class, 'delete_playlist_item' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/search', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'search' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( $namespace, '/player/queue', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'player_queue' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );
    }

    public static function get_albums( \WP_REST_Request $request ) {
        global $wpdb;
        $albums = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_albums WHERE user_id = %d", get_current_user_id() ), ARRAY_A );
        return rest_success( [ 'albums' => $albums ] );
    }

    public static function create_album( \WP_REST_Request $request ) {
        $limits = get_free_limits();
        $user   = get_current_user_id();
        if ( ! Premium::is_premium( $user ) ) {
            global $wpdb;
            $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ma_albums WHERE user_id = %d", $user ) );
            if ( $count >= (int) $limits['albums'] ) {
                return rest_error( __( 'Album limit reached.', 'music-archiver' ), 403 );
            }
        }

        $name        = sanitize_text_field( $request->get_param( 'name' ) );
        $description = wp_kses_post( $request->get_param( 'description' ) );
        $public      = (int) $request->get_param( 'is_public' );

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'ma_albums', [
            'user_id'    => $user,
            'name'       => $name,
            'description'=> $description,
            'is_public'  => $public ? 1 : 0,
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        ], [ '%d', '%s', '%s', '%d', '%s', '%s' ] );
        $album_id = (int) $wpdb->insert_id;

        do_action( 'ma_album_created', $album_id, $user );

        return rest_success( [ 'id' => $album_id ], 201 );
    }

    public static function album_detail( \WP_REST_Request $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $album = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_albums WHERE id = %d", $id ), ARRAY_A );
        if ( ! $album || (int) $album['user_id'] !== get_current_user_id() ) {
            return rest_error( __( 'Album not found.', 'music-archiver' ), 404 );
        }

        switch ( $request->get_method() ) {
            case 'GET':
                return rest_success( [ 'album' => $album ] );
            case 'PATCH':
                $params = $request->get_json_params();
                $data   = [];
                if ( isset( $params['name'] ) ) {
                    $data['name'] = sanitize_text_field( $params['name'] );
                }
                if ( isset( $params['description'] ) ) {
                    $data['description'] = wp_kses_post( $params['description'] );
                }
                if ( isset( $params['is_public'] ) ) {
                    $data['is_public'] = (int) $params['is_public'];
                }
                if ( empty( $data ) ) {
                    return rest_success( [ 'updated' => false ] );
                }
                $data['updated_at'] = current_time( 'mysql' );
                $wpdb->update( $wpdb->prefix . 'ma_albums', $data, [ 'id' => $id ], null, [ '%d' ] );
                return rest_success( [ 'updated' => true ] );
            case 'DELETE':
                $wpdb->delete( $wpdb->prefix . 'ma_albums', [ 'id' => $id ], [ '%d' ] );
                return rest_success( [ 'deleted' => true ] );
        }
    }

    public static function get_album_tracks( \WP_REST_Request $request ) {
        global $wpdb;

        $album_id = (int) $request['id'];
        $album    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_albums WHERE id = %d", $album_id ), ARRAY_A );
        if ( ! $album ) {
            return rest_error( __( 'Album not found.', 'music-archiver' ), 404 );
        }

        $user_id = get_current_user_id();
        if ( (int) $album['user_id'] !== $user_id && ! current_user_can( 'ma_manage' ) ) {
            return rest_error( __( 'Album not found.', 'music-archiver' ), 404 );
        }

        $tracks = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, at.position, at.id AS album_track_id FROM {$wpdb->prefix}ma_album_track at JOIN {$wpdb->prefix}ma_tracks t ON t.id = at.track_id WHERE at.album_id = %d ORDER BY at.position ASC", $album_id ), ARRAY_A );

        return rest_success( [ 'tracks' => $tracks ] );
    }

    public static function get_tracks( \WP_REST_Request $request ) {
        global $wpdb;

        $user_param = $request->get_param( 'user' );
        $user_id    = ( 'me' === $user_param || null === $user_param || '' === $user_param ) ? get_current_user_id() : (int) $user_param;
        $current    = get_current_user_id();

        if ( ! $user_id ) {
            return rest_success( [ 'tracks' => [] ] );
        }

        if ( $user_id !== $current && ! current_user_can( 'ma_manage' ) ) {
            return rest_error( __( 'You are not allowed to view these tracks.', 'music-archiver' ), 403 );
        }

        $tracks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_tracks WHERE user_id = %d ORDER BY created_at DESC", $user_id ), ARRAY_A );

        return rest_success( [ 'tracks' => $tracks ] );
    }

    public static function add_album_track( \WP_REST_Request $request ) {
        global $wpdb;
        $album_id = (int) $request['id'];
        $user_id  = get_current_user_id();

        $album = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_albums WHERE id = %d", $album_id ) );
        if ( ! $album || (int) $album->user_id !== $user_id ) {
            return rest_error( __( 'Album not found.', 'music-archiver' ), 404 );
        }

        $limits = get_free_limits();
        if ( ! Premium::is_premium( $user_id ) ) {
            $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ma_album_track WHERE album_id = %d", $album_id ) );
            if ( $count >= (int) $limits['tracks_per_album'] ) {
                return rest_error( __( 'Track limit reached.', 'music-archiver' ), 403 );
            }
        }

        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $title = trim( $title );
        if ( '' === $title ) {
            return rest_error( __( 'Track title is required.', 'music-archiver' ), 400 );
        }

        $source_url = trim( (string) $request->get_param( 'source_url' ) );
        $source_url = $source_url ? esc_url_raw( $source_url ) : '';
        $attachment_id = (int) $request->get_param( 'attachment_id' );

        $attachment_url = '';
        if ( $attachment_id ) {
            $attachment = get_post( $attachment_id );
            if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
                return rest_error( __( 'Selected file not found.', 'music-archiver' ), 404 );
            }

            if ( (int) $attachment->post_author !== $user_id && ! current_user_can( 'ma_manage' ) ) {
                return rest_error( __( 'You do not have permission to use this file.', 'music-archiver' ), 403 );
            }

            $attachment_url = wp_get_attachment_url( $attachment_id ) ?: '';
        }

        if ( ! $source_url && $attachment_url ) {
            $source_url = esc_url_raw( $attachment_url );
        }

        if ( ! $source_url ) {
            return rest_error( __( 'Provide an audio URL or upload a file.', 'music-archiver' ), 400 );
        }

        $wpdb->insert( $wpdb->prefix . 'ma_tracks', [
            'user_id'      => $user_id,
            'title'        => $title,
            'source_url'   => $source_url,
            'attachment_id'=> $attachment_id,
            'duration_sec' => null,
            'created_at'   => current_time( 'mysql' ),
        ], [ '%d', '%s', '%s', '%d', '%d', '%s' ] );

        $track_id = (int) $wpdb->insert_id;
        $position = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(position),0) + 1 FROM {$wpdb->prefix}ma_album_track WHERE album_id = %d", $album_id ) );

        $wpdb->insert( $wpdb->prefix . 'ma_album_track', [
            'album_id' => $album_id,
            'track_id' => $track_id,
            'position' => $position,
        ], [ '%d', '%d', '%d' ] );

        do_action( 'ma_track_added', $track_id, $album_id );

        return rest_success( [ 'track_id' => $track_id ], 201 );
    }
    public static function update_album_order( \WP_REST_Request $request ) {
        global $wpdb;
        $album_id = (int) $request['id'];
        $items    = $request->get_json_params();
        if ( ! is_array( $items ) ) {
            return rest_error( __( 'Invalid payload.', 'music-archiver' ) );
        }

        foreach ( $items as $item ) {
            if ( empty( $item['album_track_id'] ) || ! isset( $item['position'] ) ) {
                continue;
            }
            $wpdb->update( $wpdb->prefix . 'ma_album_track', [ 'position' => (int) $item['position'] ], [ 'id' => (int) $item['album_track_id'] ], [ '%d' ], [ '%d' ] );
        }

        return rest_success( [ 'ordered' => true ] );
    }

    public static function track_detail( \WP_REST_Request $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $track = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_tracks WHERE id = %d", $id ), ARRAY_A );
        if ( ! $track || (int) $track['user_id'] !== get_current_user_id() ) {
            return rest_error( __( 'Track not found.', 'music-archiver' ), 404 );
        }

        if ( 'GET' === $request->get_method() ) {
            return rest_success( [ 'track' => $track ] );
        }

        $wpdb->delete( $wpdb->prefix . 'ma_tracks', [ 'id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . 'ma_album_track', [ 'track_id' => $id ], [ '%d' ] );

        return rest_success( [ 'deleted' => true ] );
    }

    public static function submit_rating( \WP_REST_Request $request ) {
        global $wpdb;
        $object_type = in_array( $request->get_param( 'object_type' ), [ 'album', 'track' ], true ) ? $request->get_param( 'object_type' ) : 'album';
        $object_id   = (int) $request->get_param( 'object_id' );
        $stars       = max( 1, min( 5, (int) $request->get_param( 'stars' ) ) );

        $wpdb->replace( $wpdb->prefix . 'ma_ratings', [
            'user_id'     => get_current_user_id(),
            'object_type' => $object_type,
            'object_id'   => $object_id,
            'stars'       => $stars,
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d', '%s', '%d', '%d', '%s' ] );

        return rest_success( [ 'saved' => true ] );
    }

    public static function toggle_favourite( \WP_REST_Request $request ) {
        global $wpdb;
        $object_type = in_array( $request->get_param( 'object_type' ), [ 'album', 'track' ], true ) ? $request->get_param( 'object_type' ) : 'album';
        $object_id   = (int) $request->get_param( 'object_id' );
        $user_id     = get_current_user_id();

        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}ma_favourites WHERE user_id = %d AND object_type = %s AND object_id = %d", $user_id, $object_type, $object_id ) );
        if ( $existing ) {
            $wpdb->delete( $wpdb->prefix . 'ma_favourites', [ 'id' => $existing ], [ '%d' ] );
            return rest_success( [ 'favourited' => false ] );
        }

        $wpdb->insert( $wpdb->prefix . 'ma_favourites', [
            'user_id'     => $user_id,
            'object_type' => $object_type,
            'object_id'   => $object_id,
            'created_at'  => current_time( 'mysql' ),
        ], [ '%d', '%s', '%d', '%s' ] );

        return rest_success( [ 'favourited' => true ] );
    }

    public static function get_playlist(): \WP_REST_Response {
        global $wpdb;
        $user_id = get_current_user_id();
        $playlist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_playlists WHERE user_id = %d ORDER BY id ASC LIMIT 1", $user_id ) );
        if ( ! $playlist ) {
            $wpdb->insert( $wpdb->prefix . 'ma_playlists', [
                'user_id'    => $user_id,
                'name'       => __( 'My Playlist', 'music-archiver' ),
                'created_at' => current_time( 'mysql' ),
            ], [ '%d', '%s', '%s' ] );
            $playlist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_playlists WHERE user_id = %d ORDER BY id ASC LIMIT 1", $user_id ) );
        }

        $items = $wpdb->get_results( $wpdb->prepare( "SELECT pi.*, t.title, t.source_url FROM {$wpdb->prefix}ma_playlist_items pi JOIN {$wpdb->prefix}ma_tracks t ON t.id = pi.track_id WHERE pi.playlist_id = %d ORDER BY pi.position ASC", $playlist->id ), ARRAY_A );

        return rest_success( [ 'playlist' => $playlist, 'items' => $items ] );
    }

    public static function add_playlist_item( \WP_REST_Request $request ) {
        global $wpdb;
        $track_id = (int) $request->get_param( 'track_id' );
        $playlist = self::get_playlist()->get_data()['data']['playlist'] ?? null;
        if ( ! $playlist ) {
            return rest_error( __( 'Playlist not found.', 'music-archiver' ), 404 );
        }

        $position = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(position),0) + 1 FROM {$wpdb->prefix}ma_playlist_items WHERE playlist_id = %d", $playlist->id ) );
        $wpdb->insert( $wpdb->prefix . 'ma_playlist_items', [
            'playlist_id' => $playlist->id,
            'track_id'    => $track_id,
            'position'    => $position,
        ], [ '%d', '%d', '%d' ] );

        return rest_success( [ 'added' => true ] );
    }

    public static function update_playlist_order( \WP_REST_Request $request ) {
        global $wpdb;
        $items = $request->get_json_params();
        if ( ! is_array( $items ) ) {
            return rest_error( __( 'Invalid payload.', 'music-archiver' ) );
        }

        foreach ( $items as $item ) {
            if ( empty( $item['id'] ) || ! isset( $item['position'] ) ) {
                continue;
            }
            $wpdb->update( $wpdb->prefix . 'ma_playlist_items', [ 'position' => (int) $item['position'] ], [ 'id' => (int) $item['id'] ], [ '%d' ], [ '%d' ] );
        }

        return rest_success( [ 'ordered' => true ] );
    }

    public static function delete_playlist_item( \WP_REST_Request $request ) {
        global $wpdb;
        $id = (int) $request['id'];
        $wpdb->delete( $wpdb->prefix . 'ma_playlist_items', [ 'id' => $id ], [ '%d' ] );
        return rest_success( [ 'deleted' => true ] );
    }

    public static function search( \WP_REST_Request $request ) {
        global $wpdb;
        $term = '%' . $wpdb->esc_like( sanitize_text_field( $request->get_param( 'q' ) ) ) . '%';
        $user = get_current_user_id();
        $albums = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_albums WHERE user_id = %d AND name LIKE %s", $user, $term ), ARRAY_A );
        $tracks = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_tracks WHERE user_id = %d AND title LIKE %s", $user, $term ), ARRAY_A );
        return rest_success( [ 'albums' => $albums, 'tracks' => $tracks ] );
    }

    public static function player_queue( \WP_REST_Request $request ) {
        $source = sanitize_text_field( $request->get_param( 'source' ) );
        $queue  = [];
        global $wpdb;

        if ( str_starts_with( $source, 'album:' ) ) {
            $album_id = (int) substr( $source, 6 );
            $queue = $wpdb->get_results( $wpdb->prepare( "SELECT t.* FROM {$wpdb->prefix}ma_album_track at JOIN {$wpdb->prefix}ma_tracks t ON t.id = at.track_id WHERE at.album_id = %d ORDER BY at.position", $album_id ), ARRAY_A );
        } elseif ( str_starts_with( $source, 'playlist:' ) ) {
            $playlist_id = (int) substr( $source, 9 );
            $queue = $wpdb->get_results( $wpdb->prepare( "SELECT t.* FROM {$wpdb->prefix}ma_playlist_items pi JOIN {$wpdb->prefix}ma_tracks t ON t.id = pi.track_id WHERE pi.playlist_id = %d ORDER BY pi.position", $playlist_id ), ARRAY_A );
        } elseif ( str_starts_with( $source, 'track:' ) ) {
            $track_id = (int) substr( $source, 6 );
            $track    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ma_tracks WHERE id = %d", $track_id ), ARRAY_A );
            if ( $track ) {
                $owner_id = (int) $track['user_id'];
                if ( $owner_id === get_current_user_id() || current_user_can( 'ma_manage' ) ) {
                    $queue = [ $track ];
                }
            }
        }

        $queue = apply_filters( 'ma_player_queue', $queue, $source );

        return rest_success( [ 'queue' => $queue ] );
    }
}


