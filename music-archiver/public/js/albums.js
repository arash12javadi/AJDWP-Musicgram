(function () {
    if ( typeof window.wp === 'undefined' || typeof window.wp.apiFetch === 'undefined' ) {
        return;
    }

    const apiFetch = window.wp.apiFetch;
    const i18n = window.wp.i18n || {};
    const __ = typeof i18n.__ === 'function' ? i18n.__ : ( text ) => text;
    const sprintf = typeof i18n.sprintf === 'function' ? i18n.sprintf : ( text, value ) => text.replace( '%d', value );
    const restNonce = window.MAPlayer ? window.MAPlayer.nonce : null;

    const headers = restNonce ? { 'X-WP-Nonce': restNonce } : {};

    const escapeHtml = ( value ) => {
        if ( value == null ) {
            return '';
        }
        return String( value )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' )
            .replace( /'/g, '&#039;' );
    };

    const renderTracks = ( listEl, tracks ) => {
        if ( ! listEl ) {
            return;
        }

        const items = Array.isArray( tracks ) ? tracks : [];
        const count = items.length;
        const label = sprintf( __( '%d tracks', 'music-archiver' ), count );
        listEl.setAttribute( 'aria-label', label );

        if ( ! count ) {
            listEl.innerHTML = '<p class="ma-album-card__tracks-empty">' + escapeHtml( __( 'No tracks yet.', 'music-archiver' ) ) + '</p>';
            return;
        }

        const html = [
            '<ol class="ma-album-card__tracks-list">',
            items.map( ( track ) => {
                const title = escapeHtml( track.title || '' );
                const link = track.source_url
                    ? '<a class="ma-album-card__tracks-link" href="' + escapeHtml( track.source_url ) + '" target="_blank" rel="noopener">' + escapeHtml( __( 'Open', 'music-archiver' ) ) + '</a>'
                    : '';
                return '<li class="ma-album-card__tracks-item"><span class="ma-album-card__tracks-title">' + title + '</span>' + link + '</li>';
            } ).join( '' ),
            '</ol>'
        ].join( '' );

        listEl.innerHTML = html;
    };

    const refreshTracks = ( albumId, article ) => {
        if ( ! restNonce ) {
            return;
        }
        const listEl = article.querySelector( '[data-ma-album-track-list]' );
        if ( ! listEl ) {
            return;
        }
        listEl.setAttribute( 'aria-busy', 'true' );

        apiFetch( {
            path: 'ma/v1/albums/' + albumId + '/tracks',
            method: 'GET',
            headers: Object.assign( {}, headers )
        } )
            .then( ( response ) => {
                if ( response && Array.isArray( response.tracks ) ) {
                    renderTracks( listEl, response.tracks );
                }
            } )
            .catch( () => {
                const errorText = __( 'Unable to load tracks right now.', 'music-archiver' );
                listEl.innerHTML = '<p class="ma-album-card__tracks-error">' + escapeHtml( errorText ) + '</p>';
            } )
            .finally( () => {
                listEl.removeAttribute( 'aria-busy' );
            } );
    };

    const handleAlbumSubmit = ( event ) => {
        event.preventDefault();
        const form = event.currentTarget;
        const wrapper = form.closest( '[data-ma-album-wrapper]' );
        if ( ! wrapper ) {
            return;
        }

        const nameField = form.querySelector( 'input[name="ma_album_name"]' );
        const descriptionField = form.querySelector( 'textarea[name="ma_album_description"]' );
        const publicField = form.querySelector( 'input[name="ma_album_public"]' );
        const feedbackEl = form.querySelector( '[data-ma-album-feedback]' );

        if ( feedbackEl ) {
            feedbackEl.textContent = '';
            feedbackEl.classList.remove( 'is-error', 'is-success' );
        }

        if ( ! restNonce ) {
            if ( feedbackEl ) {
                feedbackEl.textContent = __( 'You need to reload the page before creating albums.', 'music-archiver' );
                feedbackEl.classList.add( 'is-error' );
            }
            return;
        }

        const name = nameField ? nameField.value.trim() : '';
        if ( ! name ) {
            if ( feedbackEl ) {
                feedbackEl.textContent = __( 'Album name is required.', 'music-archiver' );
                feedbackEl.classList.add( 'is-error' );
            }
            if ( nameField ) {
                nameField.focus();
            }
            return;
        }

        form.classList.add( 'is-loading' );

        apiFetch( {
            path: 'ma/v1/albums',
            method: 'POST',
            headers: Object.assign( {}, headers ),
            data: {
                name,
                description: descriptionField ? descriptionField.value : '',
                is_public: publicField && publicField.checked ? 1 : 0
            }
        } )
            .then( () => {
                if ( feedbackEl ) {
                    feedbackEl.textContent = __( 'Album created successfully. Reloading…', 'music-archiver' );
                    feedbackEl.classList.add( 'is-success' );
                }
                window.setTimeout( () => window.location.reload(), 300 );
            } )
            .catch( ( error ) => {
                if ( feedbackEl ) {
                    const message = error && error.message ? error.message : __( 'Album could not be created.', 'music-archiver' );
                    feedbackEl.textContent = message;
                    feedbackEl.classList.add( 'is-error' );
                }
            } )
            .finally( () => {
                form.classList.remove( 'is-loading' );
            } );
    };

    const handleTrackSubmit = ( event ) => {
        event.preventDefault();
        const form = event.target;
        const article = form.closest( '[data-ma-album-id]' );
        if ( ! article ) {
            return;
        }

        const albumId = article.getAttribute( 'data-ma-album-id' );
        const titleField = form.querySelector( 'input[name="ma_track_title"]' );
        const urlField = form.querySelector( 'input[name="ma_track_url"]' );
        const attachmentField = form.querySelector( '[data-ma-track-attachment]' );
        const feedbackEl = form.querySelector( '[data-ma-track-feedback]' );
        const mediaNameEl = form.querySelector( '[data-ma-track-media-name]' );
        const clearButton = form.querySelector( '[data-ma-track-media-clear]' );

        const resetFeedback = () => {
            if ( feedbackEl ) {
                feedbackEl.textContent = '';
                feedbackEl.classList.remove( 'is-error', 'is-success' );
            }
        };

        resetFeedback();

        if ( ! restNonce ) {
            if ( feedbackEl ) {
                feedbackEl.textContent = __( 'You need to reload the page before adding tracks.', 'music-archiver' );
                feedbackEl.classList.add( 'is-error' );
            }
            return;
        }

        const title = titleField ? titleField.value.trim() : '';
        const url = urlField ? urlField.value.trim() : '';
        const attachmentId = attachmentField ? parseInt( attachmentField.value, 10 ) : 0;

        if ( ! title ) {
            if ( feedbackEl ) {
                feedbackEl.textContent = __( 'Track title is required.', 'music-archiver' );
                feedbackEl.classList.add( 'is-error' );
            }
            if ( titleField ) {
                titleField.focus();
            }
            return;
        }

        if ( ! url && ! attachmentId ) {
            if ( feedbackEl ) {
                feedbackEl.textContent = __( 'Provide an audio URL or upload a file.', 'music-archiver' );
                feedbackEl.classList.add( 'is-error' );
            }
            if ( urlField ) {
                urlField.focus();
            }
            return;
        }

        form.classList.add( 'is-loading' );

        const payload = { title };
        if ( url ) {
            payload.source_url = url;
        }
        if ( attachmentId ) {
            payload.attachment_id = attachmentId;
        }

        apiFetch( {
            path: 'ma/v1/albums/' + albumId + '/tracks',
            method: 'POST',
            headers: Object.assign( {}, headers ),
            data: payload
        } )
            .then( () => {
                form.reset();
                if ( mediaNameEl ) {
                    mediaNameEl.textContent = '';
                }
                if ( clearButton ) {
                    clearButton.classList.remove( 'is-visible' );
                }
                if ( feedbackEl ) {
                    feedbackEl.textContent = __( 'Track added successfully.', 'music-archiver' );
                    feedbackEl.classList.add( 'is-success' );
                }
                refreshTracks( albumId, article );
            } )
            .catch( ( error ) => {
                if ( feedbackEl ) {
                    const message = error && error.message ? error.message : __( 'Track could not be added.', 'music-archiver' );
                    feedbackEl.textContent = message;
                    feedbackEl.classList.add( 'is-error' );
                }
            } )
            .finally( () => {
                form.classList.remove( 'is-loading' );
            } );
    };

    const openMediaFrame = ( button ) => {
        if ( ! window.wp.media ) {
            return;
        }

        const form = button.closest( '[data-ma-album-track-form]' );
        if ( ! form ) {
            return;
        }

        const frame = button._maMediaFrame || window.wp.media( {
            title: __( 'Select audio file', 'music-archiver' ),
            button: { text: __( 'Use this audio', 'music-archiver' ) },
            library: { type: 'audio' },
            multiple: false
        } );

        button._maMediaFrame = frame;

        frame.off( 'select' );
        frame.on( 'select', () => {
            const selection = frame.state().get( 'selection' );
            const attachment = selection && selection.first() ? selection.first().toJSON() : null;
            const attachmentField = form.querySelector( '[data-ma-track-attachment]' );
            const urlField = form.querySelector( 'input[name="ma_track_url"]' );
            const mediaNameEl = form.querySelector( '[data-ma-track-media-name]' );
            const clearButton = form.querySelector( '[data-ma-track-media-clear]' );

            if ( attachmentField ) {
                attachmentField.value = attachment && attachment.id ? attachment.id : '';
            }
            if ( mediaNameEl ) {
                mediaNameEl.textContent = attachment && ( attachment.filename || attachment.title || attachment.name ) ? attachment.filename || attachment.title || attachment.name : '';
            }
            if ( clearButton ) {
                clearButton.classList.add( 'is-visible' );
            }
            if ( urlField && attachment && attachment.url ) {
                urlField.value = attachment.url;
            }
        } );

        frame.open();
    };

    const clearMediaSelection = ( button ) => {
        const form = button.closest( '[data-ma-album-track-form]' );
        if ( ! form ) {
            return;
        }

        const attachmentField = form.querySelector( '[data-ma-track-attachment]' );
        const mediaNameEl = form.querySelector( '[data-ma-track-media-name]' );
        if ( attachmentField ) {
            attachmentField.value = '';
        }
        if ( mediaNameEl ) {
            mediaNameEl.textContent = '';
        }
        button.classList.remove( 'is-visible' );
    };

    const initWrapper = ( wrapper ) => {
        const albumForm = wrapper.querySelector( '[data-ma-album-form]' );
        if ( albumForm ) {
            albumForm.addEventListener( 'submit', handleAlbumSubmit );
        }

        wrapper.addEventListener( 'submit', ( event ) => {
            if ( event.target && event.target.matches( '[data-ma-album-track-form]' ) ) {
                handleTrackSubmit( event );
            }
        } );

        wrapper.addEventListener( 'click', ( event ) => {
            const mediaBtn = event.target.closest( '[data-ma-track-media]' );
            if ( mediaBtn ) {
                event.preventDefault();
                openMediaFrame( mediaBtn );
                return;
            }

            const clearBtn = event.target.closest( '[data-ma-track-media-clear]' );
            if ( clearBtn ) {
                event.preventDefault();
                clearMediaSelection( clearBtn );
            }
        } );
    };

    const init = () => {
        document.querySelectorAll( '[data-ma-album-wrapper][data-ma-can-manage="1"]' ).forEach( initWrapper );
    };

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
})();

