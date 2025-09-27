<?php
/**
 * Uninstall handler for Music Archiver.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/class-db.php';
require_once __DIR__ . '/includes/class-settings.php';

$settings = get_option( 'ma_settings', [] );
$retain   = isset( $settings['retain_data'] ) ? (bool) $settings['retain_data'] : false;

if ( $retain ) {
    return;
}

MA\DB::maybe_drop_tables();

// Remove options and user meta added by the plugin.
delete_option( 'ma_settings' );
delete_option( 'ma_drive_oauth_state' );

$meta_keys = [ 'ma_is_premium' ];

foreach ( $meta_keys as $key ) {
    delete_metadata( 'user', 0, $key, '', true );
}
