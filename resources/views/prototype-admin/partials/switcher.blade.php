@props(['variant' => 'A', 'variants' => ['A', 'B', 'C'], 'labels' => []])

<div class="fixed bottom-4 left-1/2 z-50 -translate-x-1/2">
    <div class="flex items-center gap-1 rounded-full border border-border bg-popover px-2 py-1.5 text-sm text-popover-foreground">
        <button
            id="proto-prev"
            type="button"
            class="inline-flex h-7 w-7 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            aria-label="Varian sebelumnya"
        >←</button>

        <span class="px-2">
            <span class="font-medium">Varian {{ $variant }}</span>
            @if (isset($labels[$variant]))
                <span class="text-muted-foreground">· {{ $labels[$variant] }}</span>
            @endif
        </span>

        <button
            id="proto-next"
            type="button"
            class="inline-flex h-7 w-7 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            aria-label="Varian berikutnya"
        >→</button>
    </div>
</div>

<script>
    (function () {
        const variants = @json(array_values($variants));
        const current = @json($variant);
        const index = variants.indexOf(current);
        const prev = variants[(index - 1 + variants.length) % variants.length];
        const next = variants[(index + 1) % variants.length];

        function go(variant) {
            const url = new URL(window.location.href);
            url.searchParams.set('variant', variant);
            window.location.href = url.toString();
        }

        document.getElementById('proto-prev')?.addEventListener('click', () => go(prev));
        document.getElementById('proto-next')?.addEventListener('click', () => go(next));

        document.addEventListener('keydown', (event) => {
            const tag = event.target.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || event.target.isContentEditable) return;
            if (event.key === 'ArrowLeft') go(prev);
            if (event.key === 'ArrowRight') go(next);
        });
    })();
</script>
