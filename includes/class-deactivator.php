<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Deactivator {
    public static function deactivate(): void {
        $timestamp = wp_next_scheduled( 'ma_drive_sync' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'ma_drive_sync' );
        }
    }
}
