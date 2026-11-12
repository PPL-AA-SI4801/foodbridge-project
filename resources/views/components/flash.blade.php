@if (session('status'))
    <div class="mb-5 rounded-md border border-[#86EFAC] bg-[#DCFCE7] px-4 py-3 text-sm text-[#166534]" role="status" dusk="flash-status">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-5 rounded-md border border-[#FCA5A5] bg-[#FEE2E2] px-4 py-3 text-sm text-[#B91C1C]" role="alert" dusk="flash-error">
        {{ session('error') }}
    </div>
@endif
