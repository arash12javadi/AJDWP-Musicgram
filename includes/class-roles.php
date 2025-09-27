<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Roles {
    public static function register_roles(): void {
        $caps = self::get_capabilities();

        add_role( 'music_archiver', __( 'Music Archiver', 'music-archiver' ), $caps['role'] );

        $roles = [ 'subscriber', 'contributor', 'author', 'editor', 'administrator' ];
        foreach ( $roles as $role_name ) {
            $role = get_role( $role_name );
            if ( ! $role ) {
                continue;
            }
            foreach ( $caps['map'] as $cap ) {
                $role->add_cap( $cap );
            }
        }
    }

    public static function remove_roles(): void {
        remove_role( 'music_archiver' );
    }

    public static function get_capabilities(): array {
        return [
            'role' => [
                'read'       => true,
                'upload_files' => true,
            ],
            'map' => [
                'ma_manage',
                'ma_edit_own',
                'ma_edit_public',
            ],
        ];
    }
}
