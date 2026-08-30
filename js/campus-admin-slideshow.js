(function () {
    const root = document.querySelector('[data-campus-slideshow]');

    if (!root) {
        return;
    }

    const slides = Array.from(root.querySelectorAll('[data-slide]'));
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (slides.length < 2 || prefersReduced) {
        return;
    }

    let index = 0;
    let timer = null;
    const intervalMs = 10000;

    function setActive(nextIndex) {
        slides[index].classList.remove('is-active');
        slides[index].setAttribute('aria-hidden', 'true');

        index = (nextIndex + slides.length) % slides.length;

        slides[index].classList.add('is-active');
        slides[index].setAttribute('aria-hidden', 'false');
    }

    function next() {
        setActive(index + 1);
    }

    function stop() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function start() {
        stop();
        timer = window.setInterval(next, intervalMs);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });

    start();
})();
