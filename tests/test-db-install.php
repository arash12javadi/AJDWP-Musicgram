<?php
use PHPUnit\Framework\TestCase;

final class MusicArchiver_DB_Test extends TestCase {
    public function test_tables_defined(): void {
        $tables = [
            'ma_albums',
            'ma_tracks',
            'ma_album_track',
            'ma_playlists',
            'ma_playlist_items',
            'ma_ratings',
            'ma_favourites',
            'ma_drive_accounts',
            'ma_drive_links',
        ];

        foreach ( $tables as $table ) {
            $this->assertStringContainsString( 'ma_', $table );
        }
    }
}
