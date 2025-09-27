(function(){
    const api = window.wp && window.wp.apiFetch;
    const config = window.MAPlayer || {};
    if (!api) {
        return;
    }

    document.addEventListener('click', function(evt){
        const button = evt.target.closest('[data-ma-rating]');
        if (!button) {
            return;
        }
        evt.preventDefault();
        const wrapper = button.closest('[data-ma-rating-object]');
        if (!wrapper) {
            return;
        }
        const objectType = wrapper.getAttribute('data-ma-rating-object');
        const objectId = wrapper.getAttribute('data-ma-rating-id');
        const stars = button.getAttribute('data-ma-rating');

        api({
            path: 'ma/v1/ratings',
            method: 'POST',
            headers: { 'X-WP-Nonce': config.nonce },
            data: { object_type: objectType, object_id: objectId, stars: stars }
        }).then(() => {
            wrapper.querySelectorAll('[data-ma-rating]').forEach(el => {
                const pressed = el.getAttribute('data-ma-rating') === stars;
                el.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            });
        });
    });
})();
