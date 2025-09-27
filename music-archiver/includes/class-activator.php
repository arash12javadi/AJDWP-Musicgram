<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Activator {
    public static function activate(): void {
        require_once MA_PATH . 'includes/class-db.php';
        require_once MA_PATH . 'includes/class-roles.php';

        DB::maybe_create_tables();
        Roles::register_roles();

        if ( ! wp_next_scheduled( 'ma_drive_sync' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'ma_drive_sync' );
        }
    }
}
