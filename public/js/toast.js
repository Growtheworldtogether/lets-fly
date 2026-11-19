/* Toast notifications: auto-dismiss, pause on hover or focus, close button, window.toast(message, type). */
(function () {
    var host = document.getElementById('toasts');
    if (!host) { return; }
    var ICONS = {
        ok: '<path d="M5 12.5l4.2 4.2L19 7"/>',
        bad: '<path d="M12 7v6M12 17h.01"/><circle cx="12" cy="12" r="9"/>',
        warn: '<path d="M12 8v5M12 16.5h.01"/><path d="M12 3l10 18H2z"/>',
        info: '<path d="M12 11v6M12 7.5h.01"/><circle cx="12" cy="12" r="9"/>'
    };
    function dismiss(el) {
        if (!el || el.dataset.gone) { return; }
        el.dataset.gone = '1';
        el.classList.add('leaving');
        setTimeout(function () { el.remove(); }, 220);
    }
    function arm(el) {
        var life = el.classList.contains('bad') ? 9000 : 6000, timer, left = life, started;
        function start() { started = Date.now(); timer = setTimeout(function () { dismiss(el); }, left); }
        function stop() { clearTimeout(timer); left -= Date.now() - started; if (left < 1500) { left = 1500; } }
        el.addEventListener('mouseenter', stop); el.addEventListener('mouseleave', start);
        el.addEventListener('focusin', stop); el.addEventListener('focusout', start);
        var x = el.querySelector('.toast-x');
        if (x) { x.addEventListener('click', function () { dismiss(el); }); }
        el.addEventListener('keydown', function (e) { if (e.key === 'Escape') { dismiss(el); } });
        start();
    }
    window.toast = function (message, type) {
        type = ICONS[type] ? type : 'info';
        var el = document.createElement('div');
        el.className = 'toast ' + type;
        el.setAttribute('role', type === 'bad' ? 'alert' : 'status');
        el.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true">' + ICONS[type] + '</svg><p></p><button type="button" class="toast-x" aria-label="Dismiss">&times;</button>';
        el.querySelector('p').textContent = message;
        host.appendChild(el);
        arm(el);
        while (host.children.length > 4) { dismiss(host.firstElementChild); }
    };
    Array.prototype.forEach.call(host.querySelectorAll('.toast'), arm);

    // After a failed submit, bring the first field error into view (skipped for errors inside a closed dialog).
    var firstErr = document.querySelector('.err');
    if (firstErr && firstErr.offsetParent !== null) {
        var r = firstErr.getBoundingClientRect();
        if (r.top < 0 || r.bottom > window.innerHeight) { firstErr.scrollIntoView({ block: 'center' }); }
    }
})();
