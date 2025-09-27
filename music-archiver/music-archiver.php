<?php
/**
 * Plugin Name: Music Archiver
 * Plugin URI: https://example.com/music-archiver
 * Description: Albums, tracks, playlists, ratings, favourites, HTML5 player, Google Drive sync, and premium limits.
 * Version: 1.0.0
 * Author: OpenAI
 * Author URI: https://example.com
 * License: GPL-2.0-or-later
 * Text Domain: music-archiver
 * Requires at least: 6.6
 * Requires PHP: 8.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MA_VERSION' ) ) {
    define( 'MA_VERSION', '1.0.0' );
}

if ( ! defined( 'MA_PATH' ) ) {
    define( 'MA_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'MA_URL' ) ) {
    define( 'MA_URL', plugin_dir_url( __FILE__ ) );
}

require_once MA_PATH . 'includes/helpers.php';
require_once MA_PATH . 'includes/class-plugin.php';
require_once MA_PATH . 'includes/class-activator.php';
require_once MA_PATH . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, [ 'MA\\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'MA\\Deactivator', 'deactivate' ] );

add_action( 'plugins_loaded', static function () {
    load_plugin_textdomain( 'music-archiver', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    $plugin = new MA\Plugin();
    $plugin->init();
} );
