@extends('layouts.dashboard-modern')

@section('title', 'Typography')
@section('page_heading', $settingsPageHeading ?? 'Typography')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please fix the highlighted fields and try again.</div>
    @endif

    <section class="ui-section">
        <div class="flex items-start gap-2">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center mt-0.5" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16"></path><path d="M6 20V6h12v14"></path><path d="M8 10h8"></path></svg>
            </span>
            <div>
                <h3 class="text-lg font-extrabold">Typography</h3>
                <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Choose a system font for the application interface.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.typography.update') }}" class="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
            @csrf
            @php $currentFont = old('brand_font_family', $companySettings['brand_font_family']); @endphp

            <div class="space-y-4">
                <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <h4 class="text-sm font-extrabold">Interface Font</h4>
                    <p class="mt-1 text-xs" style="color: var(--hr-text-muted);">Pick a font stack optimized for enterprise UI.</p>
                    <input type="hidden" id="brand_font_family" name="brand_font_family" value="{{ $currentFont }}" />

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3" role="listbox" aria-label="Choose interface font">
                        @foreach($brandFontOptions as $fontKey => $fontMeta)
                            <button type="button"
                                    class="font-card rounded-xl border p-3 text-left"
                                    style="border-color: var(--hr-line); background: var(--hr-surface); font-family: {{ $fontMeta['stack'] }};"
                                    data-font-card data-font-key="{{ $fontKey }}"
                                    aria-selected="{{ $currentFont === $fontKey ? 'true' : 'false' }}">
                                <p class="text-sm font-bold">{{ $fontMeta['label'] }}</p>
                                <p class="text-base mt-2">Aa Bb Cc 123</p>
                            </button>
                        @endforeach
                    </div>
                    @error('brand_font_family')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="ui-btn ui-btn-primary">Save</button>
                    <a href="{{ route('settings.index') }}" class="ui-btn ui-btn-ghost">Back to Settings</a>
                </div>
            </div>

            <aside class="hidden xl:block">
                <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong); font-family: var(--hr-font-family, inherit);">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-extrabold">Preview</h4>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" style="background: var(--hr-accent-soft); color: var(--hr-accent);">Live</span>
                    </div>
                    <div class="mt-3 space-y-2">
                        <p class="text-xl font-extrabold">Heading Bold</p>
                        <p class="text-sm">Body text preview with numbers 123456 and symbols &amp; % $.</p>
                        <button type="button" class="ui-btn ui-btn-primary">Primary Action</button>
                        <button type="button" class="ui-btn ui-btn-ghost">Secondary</button>
                    </div>
                </div>
            </aside>
        </form>
    </section>

    @push('scripts')
        <script>
            (function(){
                const container = document.currentScript.closest('body');
                const hidden = container.querySelector('#brand_font_family');
                const cards = container.querySelectorAll('[data-font-card]');
                function sync() {
                    cards.forEach(btn => {
                        const isActive = btn.getAttribute('data-font-key') === hidden.value;
                        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        btn.style.outline = isActive ? '2px solid var(--hr-accent)' : 'none';
                        btn.style.boxShadow = isActive ? '0 0 0 2px color-mix(in oklab, var(--hr-accent), transparent 80%)' : 'none';
                    });
                    // Update preview font family variable for live feel
                    const selected = Array.from(cards).find(b => b.getAttribute('data-font-key') === hidden.value);
                    if (selected) {
                        const fam = window.getComputedStyle(selected).fontFamily;
                        document.documentElement.style.setProperty('--hr-font-family', fam);
                    }
                }
                cards.forEach(btn => btn.addEventListener('click', () => { hidden.value = btn.getAttribute('data-font-key'); sync(); }));
                sync();
            })();
        </script>
    @endpush
@endsection

