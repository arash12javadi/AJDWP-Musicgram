<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

class Premium {
    public static function init(): void {
        add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
        add_action( 'woocommerce_order_status_completed', [ self::class, 'handle_wc_complete' ] );
    }

    public static function register_routes(): void {
        register_rest_route( 'ma/v1', '/limits', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'rest_limits' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'ma/v1', '/premium/status', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'rest_status' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( 'ma/v1', '/premium/stripe/session', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'rest_stripe_session' ],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ] );

        register_rest_route( 'ma/v1', '/stripe/webhook', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'rest_stripe_webhook' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public static function rest_limits(): \WP_REST_Response {
        return rest_success( [ 'free' => get_free_limits() ] );
    }

    public static function rest_status(): \WP_REST_Response {
        $user_id = get_current_user_id();
        return rest_success( [ 'is_premium' => self::is_premium( $user_id ) ] );
    }

    public static function rest_stripe_session( \WP_REST_Request $request ) {
        $settings = get_settings();
        if ( 'stripe' !== $settings['premium_mode'] ) {
            return rest_error( __( 'Stripe mode is disabled.', 'music-archiver' ), 400 );
        }

        if ( empty( $settings['stripe_secret'] ) || empty( $settings['stripe_price_id'] ) ) {
            return rest_error( __( 'Stripe is not configured.', 'music-archiver' ), 400 );
        }

        $body = [
            'success_url' => esc_url_raw( add_query_arg( 'ma_stripe_success', '1', home_url() ) ),
            'cancel_url'  => esc_url_raw( add_query_arg( 'ma_stripe_cancel', '1', home_url() ) ),
            'mode'        => 'subscription',
            'line_items'  => [
                [ 'price' => $settings['stripe_price_id'], 'quantity' => 1 ],
            ],
            'client_reference_id' => get_current_user_id(),
        ];

        $response = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', [
            'body'    => $body,
            'headers' => [ 'Authorization' => 'Bearer ' . $settings['stripe_secret'] ],
        ] );

        if ( is_wp_error( $response ) ) {
            return rest_error( $response->get_error_message(), 400 );
        }

        $payload = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $payload['url'] ) ) {
            return rest_error( __( 'Unable to create session.', 'music-archiver' ), 400 );
        }

        return rest_success( [ 'url' => esc_url_raw( $payload['url'] ) ] );
    }

    public static function rest_stripe_webhook( \WP_REST_Request $request ) {
        $body = $request->get_body();
        $payload = json_decode( $body, true );
        if ( empty( $payload['type'] ) ) {
            return rest_error( __( 'Invalid webhook.', 'music-archiver' ), 400 );
        }

        if ( 'checkout.session.completed' === $payload['type'] && ! empty( $payload['data']['object']['client_reference_id'] ) ) {
            $user_id = (int) $payload['data']['object']['client_reference_id'];
            update_user_meta( $user_id, 'ma_is_premium', 1 );
        }

        return rest_success( [ 'received' => true ] );
    }

    public static function handle_wc_complete( $order_id ): void {
        $settings = get_settings();
        if ( 'woocommerce' !== $settings['premium_mode'] || empty( $settings['premium_product'] ) ) {
            return;
        }

        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            if ( (int) $item->get_product_id() === (int) $settings['premium_product'] ) {
                update_user_meta( $order->get_user_id(), 'ma_is_premium', 1 );
            }
        }
    }

    public static function is_premium( int $user_id ): bool {
        if ( ! $user_id ) {
            return false;
        }

        return (bool) get_user_meta( $user_id, 'ma_is_premium', true );
    }
}
