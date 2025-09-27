import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { user, public: isPublic } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Album List Settings', 'music-archiver' ) }>
                    <TextControl
                        label={ __( 'User', 'music-archiver' ) }
                        value={ user }
                        onChange={ ( value ) => setAttributes( { user: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Public albums only', 'music-archiver' ) }
                        checked={ isPublic }
                        onChange={ ( value ) => setAttributes( { public: value } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div className="ma-album-list-block">
                <p>{ __( 'Music Archiver album list will render on the front end.', 'music-archiver' ) }</p>
            </div>
        </>
    );
}
