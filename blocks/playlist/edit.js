import { __ } from '@wordpress/i18n';

export default function Edit() {
    return (
        <div className="ma-playlist-block">
            <p>{ __( 'Music Archiver playlist will render on the front end.', 'music-archiver' ) }</p>
        </div>
    );
}
