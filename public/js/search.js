(function(){
    const api = window.wp && window.wp.apiFetch;
    const config = window.MAPlayer || {};
    if (!api) {
        return;
    }

    let timer = null;
    document.addEventListener('input', function(evt){
        const field = evt.target.closest('[data-ma-search]');
        if (!field) {
            return;
        }
        clearTimeout(timer);
        const query = field.value;
        timer = setTimeout(() => {
            if (!query) {
                return;
            }
            api({
                path: 'ma/v1/search?q=' + encodeURIComponent(query),
                headers: { 'X-WP-Nonce': config.nonce }
            }).then(resp => {
                const results = resp.data;
                const list = field.parentElement.querySelector('.ma-search-overlay__results');
                if (!list) {
                    return;
                }
                list.innerHTML = '';
                ['albums','tracks'].forEach(key => {
                    (results[key] || []).forEach(item => {
                        const li = document.createElement('div');
                        li.textContent = item.name || item.title;
                        list.appendChild(li);
                    });
                });
            });
        }, 200);
    });
})();
