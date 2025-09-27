(function () {
    if ( typeof window.wp === 'undefined' || typeof window.wp.apiFetch === 'undefined' ) {
        return;
    }

    const apiFetch = window.wp.apiFetch;
    const __ = window.wp.i18n ? window.wp.i18n.__ : ( ( text ) => text );
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

    const renderAlbums = ( listEl, albums ) => {
        if ( ! listEl ) {
            return;
        }

        if ( ! Array.isArray( albums ) || albums.length === 0 ) {
            listEl.innerHTML = '<p class="ma-album-card__empty">' + escapeHtml( __( 'No albums found.', 'music-archiver' ) ) + '</p>';
            return;
        }

        const items = albums.map( ( album ) => {
            const title = album.name ? String( album.name ) : '';
            const description = album.description ? String( album.description ) : '';
            const playLabel = __( 'Play Album', 'music-archiver' );

            return [
                '<article class="ma-album-card" data-album-id="' + album.id + '">',
                '<h3 class="ma-album-card__title">' + escapeHtml( title ) + '</h3>',
                description
                    ? '<p class="ma-album-card__description">' + description + '</p>'
                    : '',
                '<button type="button" class="ma-album-card__play" data-ma-player-load="album:' + album.id + '">' + escapeHtml( playLabel ) + '</button>',
                '</article>'
            ].join( '' );
        } );

        listEl.innerHTML = items.join( '' );
    };

    const refreshAlbums = ( wrapper ) => {
        const listEl = wrapper.querySelector( '[data-ma-album-list]' );
        if ( ! listEl ) {
            return;
        }

        listEl.setAttribute( 'aria-busy', 'true' );

        if ( ! restNonce ) {
            listEl.removeAttribute( 'aria-busy' );
            return;
        }

        apiFetch( {
            path: 'ma/v1/albums',
            method: 'GET',
            headers: Object.assign( {}, headers )
        } )
            .then( ( response ) => {
                if ( response && Array.isArray( response.albums ) ) {
                    renderAlbums( listEl, response.albums );
                }
            } )
            .catch( () => {
                const errorText = __( 'Unable to load albums right now.', 'music-archiver' );
                listEl.innerHTML = '<p class="ma-album-card__error">' + escapeHtml( errorText ) + '</p>';
            } )
            .finally( () => {
                listEl.removeAttribute( 'aria-busy' );
            } );
    };

    const handleSubmit = ( event ) => {
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
                form.reset();
                if ( feedbackEl ) {
                    feedbackEl.textContent = __( 'Album created successfully.', 'music-archiver' );
                    feedbackEl.classList.add( 'is-success' );
                }
                refreshAlbums( wrapper );
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

    const init = () => {
        const wrappers = document.querySelectorAll( '[data-ma-album-wrapper][data-ma-can-manage="1"]' );
        wrappers.forEach( ( wrapper ) => {
            const form = wrapper.querySelector( '[data-ma-album-form]' );
            if ( form ) {
                form.addEventListener( 'submit', handleSubmit );
            }
        } );
    };

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
})();
