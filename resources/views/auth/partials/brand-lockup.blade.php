<div class="brand-lockup">
    @if (! empty($brandLogoUrl))
        <div class="brand-logo-shell" aria-hidden="true">
            <img
                src="{{ $brandLogoUrl }}"
                alt="{{ $brandCompanyName }} logo"
                class="brand-logo"
                width="140"
                height="44"
                loading="eager"
                decoding="async"
            >
        </div>
    @else
        <div class="brand-logo-shell" aria-hidden="true">
            <span class="brand-fallback-mark">{{ \Illuminate\Support\Str::of($brandCompanyName)->substr(0, 1)->upper() }}</span>
        </div>
        <div class="brand-copy">
            <p class="brand-name">{{ $brandCompanyName }}</p>
        </div>
    @endif
</div>
