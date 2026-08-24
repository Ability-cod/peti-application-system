/**
 * Fades/slides each .reveal section into view as the user scrolls to
 * it. Safe by default: sections are fully visible in plain HTML/CSS
 * with no JS at all. This script only ADDS the hidden state (js-hidden)
 * once it's actually running, then removes it via IntersectionObserver
 * as each section scrolls into view — so a JS failure, slow network,
 * or disabled JavaScript never leaves content permanently invisible.
 */
document.addEventListener('DOMContentLoaded', function () {
    var sections = document.querySelectorAll('.reveal');
    if (!sections.length) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion || !('IntersectionObserver' in window)) {
        return; // leave everything visible as-is
    }

    sections.forEach(function (s) { s.classList.add('js-hidden'); });

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.remove('js-hidden');
                entry.target.classList.add('reveal-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    sections.forEach(function (s) { observer.observe(s); });
});