(function(){
    const api = window.wp && window.wp.apiFetch;
    const config = window.MAPlayer || {};
    if (!api) {
        return;
    }

    document.addEventListener('click', function(evt){
        const button = evt.target.closest('[data-ma-playlist-add]');
        if (!button) {
            return;
        }
        evt.preventDefault();
        const track = button.getAttribute('data-ma-playlist-add');
        api({
            path: 'ma/v1/playlist/items',
            method: 'POST',
            headers: { 'X-WP-Nonce': config.nonce },
            data: { track_id: track }
        });
    });

    const list = document.querySelector('[data-ma-playlist-sortable]');
    if (!list) {
        return;
    }

    let dragItem = null;
    list.addEventListener('dragstart', function(evt){
        dragItem = evt.target;
        evt.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragover', function(evt){
        evt.preventDefault();
        const target = evt.target.closest('[data-id]');
        if (target && dragItem && target !== dragItem) {
            const rect = target.getBoundingClientRect();
            const next = (evt.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
            list.insertBefore(dragItem, next ? target.nextSibling : target);
        }
    });

    list.addEventListener('dragend', function(){
        if (!dragItem) {
            return;
        }
        const payload = Array.from(list.querySelectorAll('[data-id]')).map((item, index) => ({
            id: item.getAttribute('data-id'),
            position: index
        }));
        api({
            path: 'ma/v1/playlist/order',
            method: 'PATCH',
            headers: { 'X-WP-Nonce': config.nonce },
            data: payload
        });
        dragItem = null;
    });
})();
