(function () {
    if ( typeof window.wp === 'undefined' || typeof window.wp.apiFetch === 'undefined' ) {
        return;
    }

    const apiFetch = window.wp.apiFetch;
    const i18n = window.wp.i18n || {};
    const __ = typeof i18n.__ === 'function' ? i18n.__ : ( text ) => text;
    const restNonce = window.MAPlayer ? window.MAPlayer.nonce : null;
    const headers = restNonce ? { 'X-WP-Nonce': restNonce } : {};

    const container = document.querySelector('[data-ma-playlist]');
    if ( ! container ) {
        return;
    }

    const listEl = container.querySelector('[data-ma-playlist-sortable]');
    if ( ! listEl ) {
        return;
    }

    const titleEl = container.querySelector('[data-ma-playlist-title]');
    const emptyEl = container.querySelector('[data-ma-playlist-empty]');
    const playAllBtn = container.querySelector('[data-ma-playlist-play]');

    let currentPlaylistId = null;
    let dragItem = null;

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

    const renderPlaylist = ( playlist, items ) => {
        currentPlaylistId = playlist ? playlist.id : null;

        if ( titleEl ) {
            const name = playlist && playlist.name ? playlist.name : __( 'My Playlist', 'music-archiver' );
            titleEl.textContent = name;
        }

        const hasItems = Array.isArray( items ) && items.length > 0;
        if ( playAllBtn ) {
            if ( hasItems && currentPlaylistId ) {
                playAllBtn.disabled = false;
                playAllBtn.setAttribute( 'data-ma-player-load', 'playlist:' + currentPlaylistId );
            } else {
                playAllBtn.disabled = true;
                playAllBtn.removeAttribute( 'data-ma-player-load' );
            }
        }

        if ( emptyEl ) {
            emptyEl.hidden = hasItems;
        }

        if ( ! hasItems ) {
            listEl.innerHTML = '';
            return;
        }

        const itemHtml = items.map( ( item ) => {
            const trackTitle = item.title ? item.title : __( 'Untitled track', 'music-archiver' );
            const trackId = item.track_id ? parseInt( item.track_id, 10 ) : 0;
            const canPlay = !! ( item.source_url && trackId );
            const playAttrs = canPlay ? ' data-ma-player-load="track:' + trackId + '"' : ' disabled';
            const playLabel = __( 'Play', 'music-archiver' );

            return [
                '<li class="ma-playlist__item" data-id="' + escapeHtml( String( item.id ) ) + '" draggable="true">',
                '    <span class="ma-playlist__item-title">' + escapeHtml( trackTitle ) + '</span>',
                '    <div class="ma-playlist__item-actions">',
                '        <button type="button" class="ma-playlist__item-play"' + playAttrs + '>' + escapeHtml( playLabel ) + '</button>',
                '    </div>',
                '</li>'
            ].join( '' );
        } ).join( '' );

        listEl.innerHTML = itemHtml;
    };

    const fetchPlaylist = () => apiFetch( {
        path: 'ma/v1/playlist',
        method: 'GET',
        headers: Object.assign( {}, headers )
    } );

    const refreshPlaylist = () => fetchPlaylist()
        .then( ( response ) => {
            if ( response && response.playlist ) {
                renderPlaylist( response.playlist, response.items || [] );
            } else {
                renderPlaylist( null, [] );
            }
        } )
        .catch( () => {
            renderPlaylist( null, [] );
        } );

    document.addEventListener( 'click', ( event ) => {
        const addButton = event.target.closest('[data-ma-playlist-add]');
        if ( ! addButton ) {
            return;
        }

        event.preventDefault();
        const trackId = addButton.getAttribute( 'data-ma-playlist-add' );
        if ( ! trackId ) {
            return;
        }

        apiFetch( {
            path: 'ma/v1/playlist/items',
            method: 'POST',
            headers: Object.assign( {}, headers ),
            data: { track_id: trackId }
        } )
            .then( refreshPlaylist );
    } );

    listEl.addEventListener( 'dragstart', ( event ) => {
        const item = event.target.closest('[data-id]');
        if ( ! item ) {
            return;
        }
        dragItem = item;
        event.dataTransfer.effectAllowed = 'move';
        item.classList.add( 'is-dragging' );
    } );

    listEl.addEventListener( 'dragover', ( event ) => {
        if ( ! dragItem ) {
            return;
        }

        event.preventDefault();
        const target = event.target.closest('[data-id]');
        if ( ! target || target === dragItem ) {
            return;
        }

        const rect = target.getBoundingClientRect();
        const shouldInsertAfter = ( event.clientY - rect.top ) / ( rect.bottom - rect.top ) > 0.5;
        listEl.insertBefore( dragItem, shouldInsertAfter ? target.nextSibling : target );
    } );

    listEl.addEventListener( 'dragend', () => {
        if ( ! dragItem ) {
            return;
        }
        dragItem.classList.remove( 'is-dragging' );
        dragItem = null;

        const payload = Array.from( listEl.querySelectorAll('[data-id]') ).map( ( item, index ) => ( {
            id: item.getAttribute( 'data-id' ),
            position: index
        } ) );

        if ( ! payload.length ) {
            return;
        }

        apiFetch( {
            path: 'ma/v1/playlist/order',
            method: 'PATCH',
            headers: Object.assign( {}, headers ),
            data: payload
        } )
            .then( () => refreshPlaylist() );
    } );

    refreshPlaylist();
})();
