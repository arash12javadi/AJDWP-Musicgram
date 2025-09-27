<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Settings {
    public static function register(): void {
        register_setting( 'ma_settings', 'ma_settings', [ self::class, 'sanitize' ] );

        add_settings_section( 'ma_general', __( 'General', 'music-archiver' ), '__return_false', 'ma_settings' );
        add_settings_field( 'retain_data', __( 'Retain data on uninstall', 'music-archiver' ), [ self::class, 'field_checkbox' ], 'ma_settings', 'ma_general', [ 'key' => 'retain_data' ] );
        add_settings_field( 'free_limits', __( 'Free plan limits', 'music-archiver' ), [ self::class, 'field_limits' ], 'ma_settings', 'ma_general' );

        add_settings_section( 'ma_premium', __( 'Premium', 'music-archiver' ), '__return_false', 'ma_settings' );
        add_settings_field( 'premium_mode', __( 'Premium Mode', 'music-archiver' ), [ self::class, 'field_select' ], 'ma_settings', 'ma_premium', [ 'key' => 'premium_mode', 'options' => [ 'stripe' => __( 'Stripe', 'music-archiver' ), 'woocommerce' => __( 'WooCommerce', 'music-archiver' ) ] ] );
        add_settings_field( 'premium_product', __( 'WooCommerce Product ID', 'music-archiver' ), [ self::class, 'field_text' ], 'ma_settings', 'ma_premium', [ 'key' => 'premium_product' ] );
        add_settings_field( 'stripe_price_id', __( 'Stripe Price ID', 'music-archiver' ), [ self::class, 'field_text' ], 'ma_settings', 'ma_premium', [ 'key' => 'stripe_price_id' ] );
        add_settings_field( 'stripe_publishable', __( 'Stripe Publishable Key', 'music-archiver' ), [ self::class, 'field_text' ], 'ma_settings', 'ma_premium', [ 'key' => 'stripe_publishable' ] );
        add_settings_field( 'stripe_secret', __( 'Stripe Secret Key', 'music-archiver' ), [ self::class, 'field_text' ], 'ma_settings', 'ma_premium', [ 'key' => 'stripe_secret' ] );

        add_settings_section( 'ma_drive', __( 'Google Drive', 'music-archiver' ), '__return_false', 'ma_settings' );
        add_settings_field( 'gdrive_client_id', __( 'Client ID', 'music-archiver' ), [ self::class, 'field_text' ], 'ma_settings', 'ma_drive', [ 'key' => 'gdrive_client_id' ] );
        add_settings_field( 'gdrive_client_secret', __( 'Client Secret', 'music-archiver' ), [ self::class, 'field_text' ], 'ma_settings', 'ma_drive', [ 'key' => 'gdrive_client_secret' ] );
        add_settings_field( 'gdrive_redirect_uri', __( 'Redirect URI', 'music-archiver' ), [ self::class, 'field_readonly' ], 'ma_settings', 'ma_drive', [ 'key' => 'gdrive_redirect_uri' ] );

        add_settings_section( 'ma_player', __( 'Player', 'music-archiver' ), '__return_false', 'ma_settings' );
        add_settings_field( 'player_autoplay', __( 'Autoplay', 'music-archiver' ), [ self::class, 'field_checkbox' ], 'ma_settings', 'ma_player', [ 'key' => 'player_autoplay' ] );
        add_settings_field( 'player_shuffle', __( 'Default shuffle', 'music-archiver' ), [ self::class, 'field_checkbox' ], 'ma_settings', 'ma_player', [ 'key' => 'player_shuffle' ] );
    }

    public static function sanitize( array $input ): array {
        $settings = get_settings();

        $settings['retain_data']  = ! empty( $input['retain_data'] );
        $settings['free_limits']  = [
            'albums'           => isset( $input['free_limits']['albums'] ) ? max( 0, (int) $input['free_limits']['albums'] ) : 3,
            'tracks_per_album' => isset( $input['free_limits']['tracks_per_album'] ) ? max( 0, (int) $input['free_limits']['tracks_per_album'] ) : 100,
        ];
        $settings['premium_mode'] = in_array( $input['premium_mode'] ?? 'stripe', [ 'stripe', 'woocommerce' ], true ) ? $input['premium_mode'] : 'stripe';
        $settings['premium_product'] = sanitize_text_field( $input['premium_product'] ?? '' );
        $settings['stripe_price_id'] = sanitize_text_field( $input['stripe_price_id'] ?? '' );
        $settings['stripe_publishable'] = sanitize_text_field( $input['stripe_publishable'] ?? '' );
        $settings['stripe_secret']      = sanitize_text_field( $input['stripe_secret'] ?? '' );
        $settings['gdrive_client_id'] = sanitize_text_field( $input['gdrive_client_id'] ?? '' );
        $settings['gdrive_client_secret'] = sanitize_text_field( $input['gdrive_client_secret'] ?? '' );
        $settings['gdrive_redirect_uri']  = esc_url_raw( $settings['gdrive_redirect_uri'] );
        $settings['player_autoplay'] = ! empty( $input['player_autoplay'] );
        $settings['player_shuffle']  = ! empty( $input['player_shuffle'] );

        return $settings;
    }

    public static function field_checkbox( array $args ): void {
        $settings = get_settings();
        $key      = $args['key'];
        $value    = ! empty( $settings[ $key ] );
        printf( '<label><input type="checkbox" name="ma_settings[%1$s]" value="1" %2$s /> %3$s</label>', esc_attr( $key ), checked( $value, true, false ), esc_html__( 'Enabled', 'music-archiver' ) );
    }

    public static function field_limits(): void {
        $settings = get_settings();
        $limits   = isset( $settings['free_limits'] ) ? (array) $settings['free_limits'] : [];
        $albums   = isset( $limits['albums'] ) ? (int) $limits['albums'] : 3;
        $tracks   = isset( $limits['tracks_per_album'] ) ? (int) $limits['tracks_per_album'] : 100;

        printf( '<label>%s <input type="number" min="0" name="ma_settings[free_limits][albums]" value="%d" class="small-text" /></label><br/>', esc_html__( 'Albums', 'music-archiver' ), $albums );
        printf( '<label>%s <input type="number" min="0" name="ma_settings[free_limits][tracks_per_album]" value="%d" class="small-text" /></label>', esc_html__( 'Tracks per album', 'music-archiver' ), $tracks );
    }

    public static function field_select( array $args ): void {
        $settings = get_settings();
        $key      = $args['key'];
        $value    = $settings[ $key ] ?? '';
        echo '<select name="ma_settings[' . esc_attr( $key ) . ']">';
        foreach ( $args['options'] as $option_value => $label ) {
            printf( '<option value="%1$s" %3$s>%2$s</option>', esc_attr( $option_value ), esc_html( $label ), selected( $value, $option_value, false ) );
        }
        echo '</select>';
    }

    public static function field_text( array $args ): void {
        $settings = get_settings();
        $key      = $args['key'];
        $value    = esc_attr( $settings[ $key ] ?? '' );
        printf( '<input type="text" class="regular-text" name="ma_settings[%1$s]" value="%2$s" />', esc_attr( $key ), $value );
    }

    public static function field_readonly( array $args ): void {
        $settings = get_settings();
        $key      = $args['key'];
        $value    = esc_attr( $settings[ $key ] ?? '' );
        printf( '<input type="text" readonly class="regular-text" value="%s" />', $value );
    }
}
