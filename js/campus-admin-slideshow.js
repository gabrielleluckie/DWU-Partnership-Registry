(function () {
    const root = document.querySelector('[data-campus-slideshow]');

    if (!root) {
        return;
    }

    const slides = Array.from(root.querySelectorAll('[data-slide]'));
    const dots = Array.from(root.querySelectorAll('[data-slide-dot]'));
    const prevBtn = root.querySelector('[data-slide-prev]');
    const nextBtn = root.querySelector('[data-slide-next]');
    const pauseBtn = root.querySelector('[data-slide-pause]');
    const pauseIcon = pauseBtn ? pauseBtn.querySelector('[data-pause-icon]') : null;
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (slides.length < 2) {
        return;
    }

    let index = 0;
    let timer = null;
    let paused = prefersReduced;
    const intervalMs = 10000;

    function setActive(nextIndex) {
        slides[index].classList.remove('is-active');
        slides[index].setAttribute('aria-hidden', 'true');

        if (dots[index]) {
            dots[index].classList.remove('is-active');
            dots[index].setAttribute('aria-selected', 'false');
        }

        index = (nextIndex + slides.length) % slides.length;

        slides[index].classList.add('is-active');
        slides[index].setAttribute('aria-hidden', 'false');

        if (dots[index]) {
            dots[index].classList.add('is-active');
            dots[index].setAttribute('aria-selected', 'true');
        }
    }

    function next() {
        setActive(index + 1);
    }

    function previous() {
        setActive(index - 1);
    }

    function stop() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function start() {
        stop();

        if (paused || prefersReduced) {
            return;
        }

        timer = window.setInterval(next, intervalMs);
    }

    function setPaused(nextPaused) {
        paused = nextPaused;
        stop();

        if (pauseBtn) {
            pauseBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
            pauseBtn.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
        }

        if (pauseIcon) {
            pauseIcon.classList.toggle('bi-pause-fill', !paused);
            pauseIcon.classList.toggle('bi-play-fill', paused);
        }

        if (!paused) {
            start();
        }
    }

    prevBtn?.addEventListener('click', function () {
        previous();
        start();
    });

    nextBtn?.addEventListener('click', function () {
        next();
        start();
    });

    pauseBtn?.addEventListener('click', function () {
        setPaused(!paused);
    });

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            const target = Number.parseInt(dot.getAttribute('data-slide-dot') || '0', 10);
            setActive(target);
            start();
        });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', function (event) {
        if (!root.contains(event.relatedTarget)) {
            start();
        }
    });

    root.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowRight') {
            event.preventDefault();
            next();
            start();
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            previous();
            start();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });

    start();
})();
