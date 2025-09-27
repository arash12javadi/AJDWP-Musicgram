import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { source } = attributes;
    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Player Settings', 'music-archiver' ) }>
                    <TextControl
                        label={ __( 'Source (album:ID or playlist:ID)', 'music-archiver' ) }
                        value={ source }
                        onChange={ ( value ) => setAttributes( { source: value } ) }
                    />
                </PanelBody>
            </InspectorControls>
            <div className="ma-player-block">
                <p>{ __( 'Music Archiver player will render on the front end.', 'music-archiver' ) }</p>
            </div>
        </>
    );
}
