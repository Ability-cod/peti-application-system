/**
 * Simple auto-advancing photo carousel for the homepage hero.
 * Works off whatever <img> slides are already in the DOM (rendered
 * server-side from assets/img/hero/), so there's no API call needed.
 * Respects prefers-reduced-motion by disabling auto-play for anyone
 * who has that OS setting on.
 */
document.addEventListener('DOMContentLoaded', function () {
    var carousel = document.querySelector('.hero-carousel');
    if (!carousel) return;

    var slides = carousel.querySelectorAll('.hero-slide');
    var dotsWrap = carousel.querySelector('.hero-dots');
    if (slides.length <= 1) return;

    var current = 0;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    slides.forEach(function (slide, i) {
        var dot = document.createElement('button');
        dot.className = 'hero-dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        dot.addEventListener('click', function () { goTo(i); });
        dotsWrap.appendChild(dot);
    });

    var dots = dotsWrap.querySelectorAll('.hero-dot');

    function goTo(index) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = index;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    function next() {
        goTo((current + 1) % slides.length);
    }

    if (!reduceMotion) {
        var timer = setInterval(next, 5500);
        carousel.addEventListener('mouseenter', function () { clearInterval(timer); });
        carousel.addEventListener('mouseleave', function () { timer = setInterval(next, 5500); });
    }
});