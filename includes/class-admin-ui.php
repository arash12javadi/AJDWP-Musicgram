<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Admin_UI {
    public static function register_menu(): void {
        add_menu_page(
            __( 'Music Archiver', 'music-archiver' ),
            __( 'Music Archiver', 'music-archiver' ),
            'ma_manage',
            'music-archiver',
            [ self::class, 'render_page' ],
            'dashicons-album',
            56
        );
    }

    public static function render_page(): void {
        if ( ! current_user_can_manage() ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'music-archiver' ) );
        }

        $settings = get_settings();
        ?>
        <div class="wrap ma-admin">
            <h1><?php esc_html_e( 'Music Archiver Settings', 'music-archiver' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'ma_settings' );
                wp_nonce_field( 'ma_admin_save', 'ma_admin_nonce' );
                ?>
                <div class="ma-settings">
                    <div class="ma-card">
                        <h2><?php esc_html_e( 'General', 'music-archiver' ); ?></h2>
                        <?php do_settings_fields( 'ma_settings', 'ma_general' ); ?>
                    </div>
                    <div class="ma-card">
                        <h2><?php esc_html_e( 'Premium', 'music-archiver' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Configure how premium access is granted.', 'music-archiver' ); ?></p>
                        <?php do_settings_fields( 'ma_settings', 'ma_premium' ); ?>
                    </div>
                    <div class="ma-card">
                        <h2><?php esc_html_e( 'Google Drive', 'music-archiver' ); ?></h2>
                        <p class="description"><?php esc_html_e( 'Provide OAuth credentials. Use the redirect URI when configuring the consent screen.', 'music-archiver' ); ?></p>
                        <?php do_settings_fields( 'ma_settings', 'ma_drive' ); ?>
                    </div>
                    <div class="ma-card">
                        <h2><?php esc_html_e( 'Player', 'music-archiver' ); ?></h2>
                        <?php do_settings_fields( 'ma_settings', 'ma_player' ); ?>
                    </div>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
