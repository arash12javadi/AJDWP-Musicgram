(function(){
    const api = window.wp && window.wp.apiFetch;
    const config = window.MAPlayer || {};
    if (!api) {
        return;
    }

    function fetchQueue(source) {
        return api({
            path: 'ma/v1/player/queue?source=' + encodeURIComponent(source),
            headers: { 'X-WP-Nonce': config.nonce }
        }).then(resp => resp.data.queue || []);
    }

    document.addEventListener('click', function(evt){
        const target = evt.target.closest('[data-ma-player-load]');
        if (!target) {
            return;
        }
        evt.preventDefault();
        const source = target.getAttribute('data-ma-player-load');
        const container = document.querySelector('.ma-player');
        if (!container) {
            return;
        }

        fetchQueue(source).then(queue => {
            if (!queue.length) {
                return;
            }
            const audio = container.querySelector('audio');
            const title = container.querySelector('[data-ma-player-title]');
            let index = 0;

            function playTrack(i) {
                const track = queue[i];
                if (!track) {
                    return;
                }
                audio.src = track.source_url;
                title.textContent = track.title;
                audio.play();
            }

            audio.addEventListener('ended', function(){
                index = (index + 1) % queue.length;
                playTrack(index);
            }, { once: false });

            playTrack(index);
        });
    });
})();
