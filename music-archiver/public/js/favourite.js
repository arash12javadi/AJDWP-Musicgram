(function(){
    const api = window.wp && window.wp.apiFetch;
    const config = window.MAPlayer || {};
    if (!api) {
        return;
    }

    document.addEventListener('click', function(evt){
        const button = evt.target.closest('[data-ma-favourite]');
        if (!button) {
            return;
        }
        evt.preventDefault();
        const objectType = button.getAttribute('data-ma-favourite-type');
        const objectId = button.getAttribute('data-ma-favourite-id');

        api({
            path: 'ma/v1/favourites/toggle',
            method: 'POST',
            headers: { 'X-WP-Nonce': config.nonce },
            data: { object_type: objectType, object_id: objectId }
        }).then(resp => {
            const fav = resp.data.favourited;
            button.setAttribute('aria-pressed', fav ? 'true' : 'false');
        });
    });
})();
