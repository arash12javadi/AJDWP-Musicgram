<?php
namespace MA;

defined( 'ABSPATH' ) || exit;

/**
 * Return the plugin option array with defaults.
 */
function get_settings(): array {
    $defaults = [
        'retain_data'       => false,
        'free_limits'       => [ 'albums' => 3, 'tracks_per_album' => 100 ],
        'premium_mode'      => 'stripe',
        'premium_product'   => '',
        'stripe_price_id'   => '',
        'stripe_publishable'=> '',
        'stripe_secret'     => '',
        'gdrive_client_id'  => '',
        'gdrive_client_secret' => '',
        'gdrive_redirect_uri'  => rest_url( 'ma/v1/drive/callback' ),
        'player_autoplay'      => false,
        'player_shuffle'       => false,
    ];

    $settings = get_option( 'ma_settings', [] );

    return wp_parse_args( $settings, $defaults );
}

/**
 * Build REST response helper.
 */
function rest_success( $data = [], int $status = 200 ) {
    return new \WP_REST_Response( [
        'success' => true,
        'data'    => $data,
    ], $status );
}

function rest_error( string $message, int $status = 400 ) {
    return new \WP_Error( 'ma_error', $message, [ 'status' => $status ] );
}

/**
 * Retrieve encryption key for token storage.
 */
function get_secret_key(): string {
    if ( defined( 'MA_SECRET_KEY' ) && MA_SECRET_KEY ) {
        return (string) MA_SECRET_KEY;
    }

    $keys = [ 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ];
    foreach ( $keys as $constant ) {
        if ( defined( $constant ) && constant( $constant ) ) {
            return hash( 'sha256', constant( $constant ) );
        }
    }

    return hash( 'sha256', get_site_url() . wp_salt() );
}

function encrypt_token( string $token ): string {
    if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
        return base64_encode( $token );
    }

    $key = sodium_crypto_secretbox_keygen();
    $derived = substr( hash( 'sha256', get_secret_key(), true ), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
    $nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

    $cipher = sodium_crypto_secretbox( $token, $nonce, $derived );

    return wp_json_encode( [
        'nonce' => base64_encode( $nonce ),
        'data'  => base64_encode( $cipher ),
    ] );
}

function decrypt_token( string $value ): ?string {
    if ( ! $value ) {
        return null;
    }

    if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
        return base64_decode( $value );
    }

    $decoded = json_decode( $value, true );
    if ( ! is_array( $decoded ) || empty( $decoded['nonce'] ) || empty( $decoded['data'] ) ) {
        return null;
    }

    $nonce   = base64_decode( $decoded['nonce'] );
    $cipher  = base64_decode( $decoded['data'] );
    $derived = substr( hash( 'sha256', get_secret_key(), true ), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES );

    $plain = sodium_crypto_secretbox_open( $cipher, $nonce, $derived );

    return false === $plain ? null : $plain;
}

function current_user_can_manage(): bool {
    return current_user_can( 'ma_manage' ) || current_user_can( 'manage_options' );
}

function get_free_limits(): array {
    $settings = get_settings();
    $limits   = isset( $settings['free_limits'] ) && is_array( $settings['free_limits'] ) ? $settings['free_limits'] : [];
    $limits   = wp_parse_args( $limits, [ 'albums' => 3, 'tracks_per_album' => 100 ] );

    /**
     * Filter the free plan limits.
     */
    return apply_filters( 'ma_free_limits', $limits );
}

function format_datetime( string $value ): string {
    $timestamp = strtotime( $value );
    if ( ! $timestamp ) {
        return $value;
    }

    return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
}
