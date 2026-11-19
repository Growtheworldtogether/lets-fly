/* Two-column pages: cards that sit side by side and nearly line up are snapped to the same top and bottom. */
(function () {
    function cards(col) {
        return [].filter.call(col.querySelectorAll('.panel-card'), function (c) {
            return c.offsetParent !== null && c.parentElement.closest('.panel-card') === null;
        });
    }
    function top(el) { return el.getBoundingClientRect().top; }
    function reset(c) {
        if (c.dataset.am === undefined) { c.dataset.am = c.style.marginTop || ''; c.dataset.ah = c.style.minHeight || ''; }
        c.style.marginTop = c.dataset.am; c.style.minHeight = c.dataset.ah;
    }
    function run() {
        document.querySelectorAll('.mgrid:not(.dashgrid), .cols').forEach(function (g) {
            var cols = [].filter.call(g.children, function (c) { return c.nodeType === 1 && c.offsetParent !== null; });
            if (cols.length !== 2 || cols.some(function (c) { return c.classList.contains('panel-card'); })) { return; }
            var all = cards(cols[0]).concat(cards(cols[1]));
            all.forEach(reset);
            var a = cols[0].getBoundingClientRect(), b = cols[1].getBoundingClientRect();
            if (a.right > b.left + 2 && b.right > a.left + 2) { return; }
            var L = cards(cols[0]), R = cards(cols[1]), j = 0;
            for (var i = 0; i < L.length; i++) {
                for (; j < R.length; j++) {
                    var d = top(L[i]) - top(R[j]);
                    if (d > 60) { continue; }
                    if (d < -60) { break; }
                    var low = d > 0 ? R[j] : L[i], gap = Math.abs(d);
                    if (gap >= 1) { low.style.marginTop = (parseFloat(getComputedStyle(low).marginTop) + gap) + 'px'; }
                    var e = L[i].getBoundingClientRect().bottom - R[j].getBoundingClientRect().bottom;
                    if (Math.abs(e) >= 1 && Math.abs(e) <= 80) {
                        var s = e > 0 ? R[j] : L[i];
                        s.style.minHeight = (s.getBoundingClientRect().height + Math.abs(e)) + 'px';
                    }
                    j++;
                    break;
                }
            }
            var la = L[L.length - 1], lb = R[R.length - 1];
            if (la && lb) {
                var f = la.getBoundingClientRect().bottom - lb.getBoundingClientRect().bottom;
                if (Math.abs(f) >= 1 && Math.abs(f) <= 80) {
                    var t2 = f > 0 ? lb : la;
                    t2.style.minHeight = (t2.getBoundingClientRect().height + Math.abs(f)) + 'px';
                }
            }
        });
    }
    var t;
    function later() { clearTimeout(t); t = setTimeout(run, 60); }
    document.addEventListener('DOMContentLoaded', run);
    window.addEventListener('load', run);
    window.addEventListener('resize', later);
    if (document.fonts && document.fonts.ready) { document.fonts.ready.then(run); }
})();
