=== Music Archiver ===
Contributors: openai
Tags: music, playlist, audio, google-drive, premium
Requires at least: 6.6
Tested up to: 6.6
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Music Archiver helps artists manage albums, tracks, playlists, ratings, favourites, HTML5 playback, premium upgrades, and Google Drive sync.

== Description ==

Music Archiver is an all-in-one toolkit for independent musicians and labels. Build albums, arrange tracks, publish playlists, collect ratings, save favourites, embed a responsive HTML5 player, and optionally sync audio from Google Drive. Premium limits can be enforced using Stripe Checkout or WooCommerce.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu.
3. Visit **Settings → Music Archiver** to configure limits, premium mode, and Google Drive OAuth.

== Features ==

* Custom database tables for albums, tracks, playlists, ratings, favourites, and Drive links.
* REST API (ma/v1) for CRUD, search, playlist ordering, premium status, and Drive sync.
* Gutenberg blocks for Album List, Playlist, and Player.
* Accessible HTML5 audio player with queue management.
* Optional Google Drive integration with OAuth 2.0 and background sync.
* Premium limits with Stripe Checkout or WooCommerce purchases.
* GDPR-friendly data exporters and erasers.
* Hooks for developers to extend behaviour.

== Frequently Asked Questions ==

= Does this plugin create custom post types? =
Yes. Albums and Tracks can optionally be exposed as CPTs while primary data remains in custom tables.

= How does premium mode work? =
Choose Stripe or WooCommerce in settings. Users exceeding free limits are prompted to upgrade. Developers can filter limits using `ma_free_limits`.

= What data is stored? =
Albums, tracks, playlists, ratings, favourites, and optional Google Drive tokens. Tokens are encrypted using WordPress salts. Use the privacy exporter to review or erase.

== Privacy ==

The plugin stores user-generated music data and optional OAuth tokens for Google Drive. Tokens are encrypted at rest. Exporters and erasers are registered for compliance.

== Developer Hooks ==

* `do_action( 'ma_album_created', $album_id, $user_id )`
* `do_action( 'ma_track_added', $track_id, $album_id )`
* `apply_filters( 'ma_free_limits', [ 'albums' => 3, 'tracks_per_album' => 100 ] )`
* `apply_filters( 'ma_player_queue', $queue, $source )`

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.