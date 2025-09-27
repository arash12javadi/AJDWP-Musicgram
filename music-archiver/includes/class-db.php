<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class DB {
    public static function maybe_create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $tables = [];
        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_albums (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            cover_id BIGINT UNSIGNED NULL,
            is_public TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_public (is_public)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_tracks (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            source_url TEXT NULL,
            attachment_id BIGINT UNSIGNED NULL,
            duration_sec INT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY title (title)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_album_track (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            album_id BIGINT UNSIGNED NOT NULL,
            track_id BIGINT UNSIGNED NOT NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY album_track (album_id, track_id),
            KEY album_position (album_id, position)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_playlists (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL DEFAULT 'My Playlist',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_name (user_id, name),
            KEY user_id (user_id)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_playlist_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            playlist_id BIGINT UNSIGNED NOT NULL,
            track_id BIGINT UNSIGNED NOT NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY playlist_position (playlist_id, position)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_ratings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            object_type ENUM('album','track') NOT NULL,
            object_id BIGINT UNSIGNED NOT NULL,
            stars TINYINT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_object (user_id, object_type, object_id),
            KEY object_lookup (object_type, object_id)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_favourites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            object_type ENUM('album','track') NOT NULL,
            object_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_object (user_id, object_type, object_id)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_drive_accounts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(32) NOT NULL DEFAULT 'gdrive',
            label VARCHAR(120) NOT NULL,
            token_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_provider (user_id, provider)
        ) {$charset_collate};";

        $tables[] = "CREATE TABLE {$wpdb->prefix}ma_drive_links (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            album_id BIGINT UNSIGNED NOT NULL,
            drive_folder_id VARCHAR(128) NOT NULL,
            last_synced_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY album (album_id)
        ) {$charset_collate};";

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }

    public static function maybe_drop_tables(): void {
        global $wpdb;
        $tables = [
            'ma_album_track',
            'ma_playlist_items',
            'ma_playlists',
            'ma_ratings',
            'ma_favourites',
            'ma_drive_links',
            'ma_drive_accounts',
            'ma_tracks',
            'ma_albums',
        ];

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
        }
    }
}
