@props(['variant' => 'A', 'variants' => ['A', 'B', 'C']])

<div id="prototype-switcher" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50">
    <div class="flex items-center gap-2 rounded-full bg-neutral-900 text-white shadow-lg px-3 py-1.5 text-sm">
        <button id="proto-prev" class="px-2 py-1 hover:bg-neutral-800 rounded-full" aria-label="Previous variant">←</button>
        <span class="px-2">Variant {{ $variant }}</span>
        <button id="proto-next" class="px-2 py-1 hover:bg-neutral-800 rounded-full" aria-label="Next variant">→</button>
    </div>
</div>

<script>
    (function() {
        const variants = @json($variants);
        const current = @json($variant);
        const idx = variants.indexOf(current);
        const prev = variants[(idx - 1 + variants.length) % variants.length];
        const next = variants[(idx + 1) % variants.length];
        const base = window.location.pathname;

        const prevBtn = document.getElementById('proto-prev');
        const nextBtn = document.getElementById('proto-next');

        if (prevBtn) prevBtn.addEventListener('click', function() {
            window.location.search = '?variant=' + encodeURIComponent(prev);
        });
        if (nextBtn) nextBtn.addEventListener('click', function() {
            window.location.search = '?variant=' + encodeURIComponent(next);
        });

        document.addEventListener('keydown', function(e) {
            const tag = e.target.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || e.target.isContentEditable) return;
            if (e.key === 'ArrowLeft') {
                window.location.search = '?variant=' + encodeURIComponent(prev);
            } else if (e.key === 'ArrowRight') {
                window.location.search = '?variant=' + encodeURIComponent(next);
            }
        });
    })();
</script>
