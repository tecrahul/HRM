@extends('layouts.dashboard-modern')

@section('title', 'Themes')
@section('page_heading', $settingsPageHeading ?? 'Themes')

@section('content')
    @if (session('status'))
        <div class="ui-alert ui-alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="ui-alert ui-alert-danger">Please fix the highlighted fields and try again.</div>
    @endif

    <section class="ui-section">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="flex items-start gap-2">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center mt-0.5" style="background: var(--hr-accent-soft); color: var(--hr-accent);">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14 3-11 18"></path><path d="M9 3h5v5"></path><path d="M5 19h5v5"></path></svg>
                </span>
                <div>
                    <h3 class="text-lg font-extrabold">Brand Theme</h3>
                    <p class="text-sm mt-1" style="color: var(--hr-text-muted);">Customize brand colors and preview your theme live.</p>
                </div>
            </div>
        </div>

        <form id="themeStudioForm" method="POST" action="{{ route('settings.themes.update') }}" class="mt-5 grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
            @csrf
            @php
                $currentPrimaryColor = strtoupper((string) old('brand_primary_color', $companySettings['brand_primary_color']));
                if (! preg_match('/^#[0-9A-F]{6}$/', $currentPrimaryColor)) {
                    $currentPrimaryColor = '#7C3AED';
                }
                $currentSecondaryColor = strtoupper((string) old('brand_secondary_color', $companySettings['brand_secondary_color']));
                if (! preg_match('/^#[0-9A-F]{6}$/', $currentSecondaryColor)) {
                    $currentSecondaryColor = '#5EEAD4';
                }
                $brandColorPresets = [
                    ['name' => 'Violet + Mint', 'primary' => '#7C3AED', 'secondary' => '#5EEAD4'],
                    ['name' => 'Indigo + Amber', 'primary' => '#4F46E5', 'secondary' => '#F59E0B'],
                    ['name' => 'Emerald + Slate', 'primary' => '#059669', 'secondary' => '#334155'],
                    ['name' => 'Rose + Navy', 'primary' => '#E11D48', 'secondary' => '#1E3A8A'],
                    ['name' => 'Orange + Charcoal', 'primary' => '#EA580C', 'secondary' => '#1F2937'],
                    // New enterprise-grade combinations
                    ['name' => 'Graphite + Lime', 'primary' => '#1F2937', 'secondary' => '#84CC16'],
                    ['name' => 'Cobalt + Coral', 'primary' => '#2563EB', 'secondary' => '#F97316'],
                    ['name' => 'Midnight + Teal', 'primary' => '#0F172A', 'secondary' => '#14B8A6'],
                ];
            @endphp

            <!-- Left: Tabs and content -->
            <div class="space-y-4">
                <!-- Tabs -->
                <div class="rounded-xl border p-1.5" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <div class="ui-tabs" role="tablist" aria-label="Theme options">
                        <button type="button" class="ui-tab" role="tab" aria-selected="true" aria-controls="tab_presets" id="tab_presets_btn">Presets</button>
                        <button type="button" class="ui-tab" role="tab" aria-selected="false" aria-controls="tab_custom" id="tab_custom_btn">Custom Theme</button>
                    </div>
                </div>

                <!-- Presets tab panel -->
                <section id="tab_presets" role="tabpanel" aria-labelledby="tab_presets_btn" class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                    <h4 class="text-sm font-extrabold">Theme Presets</h4>
                    <div id="presetsGrid" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($brandColorPresets as $index => $preset)
                            <button type="button"
                                class="theme-card relative rounded-xl border p-2 flex flex-col gap-2 text-left"
                                style="border-color: var(--hr-line); background: var(--hr-surface);"
                                data-brand-preset
                                data-index="{{ $index }}"
                                data-brand-preset-primary="{{ $preset['primary'] }}"
                                data-brand-preset-secondary="{{ $preset['secondary'] }}"
                                aria-pressed="false"
                            >
                                <span class="selected-badge" aria-hidden="true">Selected</span>
                                <div>
                                    <p class="text-sm font-bold">{{ $preset['name'] }}</p>
                                    <p class="text-xs mt-0.5" style="color: var(--hr-text-muted);">{{ $preset['primary'] }} • {{ $preset['secondary'] }}</p>
                                </div>
                                <div class="rounded-lg overflow-hidden border" style="border-color: var(--hr-line)">
                                    <div class="h-12 w-full" style="background: linear-gradient(90deg, {{ $preset['primary'] }} 0%, {{ $preset['primary'] }} 50%, {{ $preset['secondary'] }} 50%, {{ $preset['secondary'] }} 100%);"></div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-1" id="presetsPagination" aria-label="Presets pages">
                            <button type="button" class="ui-btn ui-btn-ghost" id="presetsPrev" aria-label="Previous page" style="padding: 2px 8px; font-size: 12px;">‹</button>
                            <span class="text-xs" id="presetsPageLabel" style="color: var(--hr-text-muted);">Page 1 of 2</span>
                            <button type="button" class="ui-btn ui-btn-ghost" id="presetsNext" aria-label="Next page" style="padding: 2px 8px; font-size: 12px;">›</button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="ui-btn ui-btn-secondary" id="previewSelectedBtn">Preview Selected</button>
                            <button type="submit" class="ui-btn ui-btn-primary" id="applyThemeBtn">Apply Theme</button>
                        </div>
                    </div>
                </section>

                <!-- Custom tab panel -->
                <section id="tab_custom" role="tabpanel" aria-labelledby="tab_custom_btn" class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);" hidden>
                    <h4 class="text-sm font-extrabold">Custom Theme</h4>
                    <p class="sr-only">Adjust brand colors</p>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <div class="space-y-4">
                            <!-- Primary color with popover -->
                            <div class="relative">
                                <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Primary Color</label>
                                <div class="flex items-center gap-2">
                                    <input id="brand_primary_color" name="brand_primary_color" type="text" value="{{ $currentPrimaryColor }}" class="ui-input" placeholder="#7C3AED" data-brand-color-text="primary">
                                    <button type="button" class="ui-btn ui-btn-secondary" data-popover-open="primary" aria-haspopup="dialog" aria-expanded="false" aria-controls="popover_primary">
                                        <span class="h-4 w-4 rounded-full inline-block" style="background: {{ $currentPrimaryColor }}"></span>
                                        Pick
                                    </button>
                                </div>
                                @error('brand_primary_color')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <div id="popover_primary" class="color-popover" hidden>
                                    <div class="color-popover__panel">
                                        <div class="flex items-center gap-2">
                                            <input id="brand_primary_color_picker" type="color" value="{{ $currentPrimaryColor }}" class="color-popover__picker" data-brand-color-picker="primary" aria-label="Choose primary brand color">
                                            <input type="text" class="ui-input" data-hex-input="primary" value="{{ $currentPrimaryColor }}" aria-label="Primary HEX">
                                        </div>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.08em] font-bold" style="color: var(--hr-text-muted);">RGB</p>
                                                <p class="text-sm font-semibold" data-rgb-display="primary">—</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.08em] font-bold" style="color: var(--hr-text-muted);">Contrast (on white)</p>
                                                <p class="text-sm font-semibold" data-contrast-display="primary">—</p>
                                            </div>
                                        </div>
                                        <div class="mt-3"><span class="preview-chip" data-contrast-chip="primary">Aa</span></div>
                                        <div class="mt-3 flex justify-end gap-2">
                                            <button type="button" class="ui-btn ui-btn-ghost" data-popover-close="primary">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Secondary color with popover -->
                            <div class="relative">
                                <label class="block text-xs font-semibold uppercase tracking-[0.08em] mb-2" style="color: var(--hr-text-muted);">Secondary Color</label>
                                <div class="flex items-center gap-2">
                                    <input id="brand_secondary_color" name="brand_secondary_color" type="text" value="{{ $currentSecondaryColor }}" class="ui-input" placeholder="#5EEAD4" data-brand-color-text="secondary">
                                    <button type="button" class="ui-btn ui-btn-secondary" data-popover-open="secondary" aria-haspopup="dialog" aria-expanded="false" aria-controls="popover_secondary">
                                        <span class="h-4 w-4 rounded-full inline-block" style="background: {{ $currentSecondaryColor }}"></span>
                                        Pick
                                    </button>
                                </div>
                                @error('brand_secondary_color')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <div id="popover_secondary" class="color-popover" hidden>
                                    <div class="color-popover__panel">
                                        <div class="flex items-center gap-2">
                                            <input id="brand_secondary_color_picker" type="color" value="{{ $currentSecondaryColor }}" class="color-popover__picker" data-brand-color-picker="secondary" aria-label="Choose secondary brand color">
                                            <input type="text" class="ui-input" data-hex-input="secondary" value="{{ $currentSecondaryColor }}" aria-label="Secondary HEX">
                                        </div>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.08em] font-bold" style="color: var(--hr-text-muted);">RGB</p>
                                                <p class="text-sm font-semibold" data-rgb-display="secondary">—</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] uppercase tracking-[0.08em] font-bold" style="color: var(--hr-text-muted);">Contrast (on white)</p>
                                                <p class="text-sm font-semibold" data-contrast-display="secondary">—</p>
                                            </div>
                                        </div>
                                        <div class="mt-3"><span class="preview-chip" data-contrast-chip="secondary">Aa</span></div>
                                        <div class="mt-3 flex justify-end gap-2">
                                            <button type="button" class="ui-btn ui-btn-ghost" data-popover-close="secondary">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="ui-btn ui-btn-secondary" id="previewChangesBtn">Preview Changes</button>
                                <button type="submit" class="ui-btn ui-btn-primary" id="applyThemeBtn">Apply Theme</button>
                                <button type="reset" class="ui-btn ui-btn-ghost">Reset</button>
                            </div>
                        </div>

                        <!-- Right side of Custom tab uses the same Live Preview -->
                        <div class="xl:hidden">
                            <div id="themePreview" class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong); font-family: var(--hr-font-family, inherit); --hr-accent: var(--preview-primary, var(--hr-accent)); --hr-accent-border: var(--preview-primary, var(--hr-accent)); --hr-accent-soft: color-mix(in oklab, var(--preview-primary, var(--hr-accent)), #fff 85%);">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-extrabold">Live Preview</h4>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" style="background: var(--hr-accent-soft); color: var(--hr-accent);">Not saved</span>
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-3">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="ui-btn ui-btn-primary">Primary</button>
                                        <button type="button" class="ui-btn ui-btn-ghost">Secondary</button>
                                    </div>
                                    <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                        <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Card Sample</p>
                                        <p class="text-sm mt-2">A calmer preview of typical content.</p>
                                        <div class="mt-3 flex items-center gap-2">
                                            <button type="button" class="ui-btn ui-btn-primary">Action</button>
                                            <button type="button" class="ui-btn ui-btn-ghost">Cancel</button>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                        <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Accents</p>
                                        <div class="h-8 w-full rounded-lg mt-2" style="background: var(--preview-primary, var(--hr-accent));"></div>
                                        <div class="h-8 w-full rounded-lg mt-2" style="background: var(--preview-secondary, var(--hr-accent-soft));"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Right: Live Preview Panel for Presets tab (hidden on small screens) -->
            <aside class="hidden xl:block">
                <div id="themePreviewStandalone" class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong); font-family: var(--hr-font-family, inherit); --hr-accent: var(--preview-primary, var(--hr-accent)); --hr-accent-border: var(--preview-primary, var(--hr-accent)); --hr-accent-soft: color-mix(in oklab, var(--preview-primary, var(--hr-accent)), #fff 85%);">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-extrabold">Live Preview</h4>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" style="background: var(--hr-accent-soft); color: var(--hr-accent);">Not saved</span>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-3">
                        <div class="flex items-center gap-2">
                            <button type="button" class="ui-btn ui-btn-primary">Primary</button>
                            <button type="button" class="ui-btn ui-btn-ghost">Secondary</button>
                        </div>
                        <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                            <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Card Sample</p>
                            <p class="text-sm mt-2">A calmer preview of typical content.</p>
                            <div class="mt-3 flex items-center gap-2">
                                <button type="button" class="ui-btn ui-btn-primary">Action</button>
                                <button type="button" class="ui-btn ui-btn-ghost">Cancel</button>
                            </div>
                        </div>
                        <div class="rounded-xl border p-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                            <p class="text-xs font-semibold uppercase tracking-[0.08em]" style="color: var(--hr-text-muted);">Accents</p>
                            <div class="h-8 w-full rounded-lg mt-2" style="background: var(--preview-primary, var(--hr-accent));"></div>
                            <div class="h-8 w-full rounded-lg mt-2" style="background: var(--preview-secondary, var(--hr-accent-soft));"></div>
                        </div>
                    </div>
                </div>
            </aside>
        </form>
    </section>

@push('head')
    <style>
        .ui-tabs {
            display: flex; gap: 6px; padding: 2px; border-radius: 12px; background: var(--hr-surface);
        }
        .ui-tab {
            appearance: none; border: 1px solid var(--hr-line); background: transparent; color: inherit; font-weight: 800;
            font-size: 12px; padding: 6px 10px; border-radius: 10px; transition: all 160ms ease;
        }
        .ui-tab[aria-selected="true"] {
            background: var(--hr-accent-soft); color: var(--hr-accent); border-color: var(--hr-accent-border);
        }

        .theme-card { transition: box-shadow 160ms ease, border-color 160ms ease, transform 160ms ease; }
        .theme-card:hover { transform: translateY(-1px); border-color: var(--hr-accent-border); }
        .theme-card.is-active { box-shadow: 0 0 0 2px var(--hr-accent-border), 0 18px 30px -22px color-mix(in oklab, var(--hr-accent), #000 26%); }
        .selected-badge {
            position: absolute; top: 8px; right: 8px; display: none; font-size: 10px; font-weight: 800;
            padding: 2px 6px; border-radius: 999px; background: var(--hr-accent-soft); color: var(--hr-accent);
        }
        .theme-card.is-active .selected-badge { display: inline-block; }

        /* Font selector removed: CSS cleaned up */

        .color-popover { position: absolute; z-index: var(--z-popover, 1200); inset: auto auto auto 0; }
        .color-popover__panel {
            background: var(--hr-surface-strong);
            border: 1px solid var(--hr-line);
            border-radius: 12px;
            padding: 12px;
            box-shadow: var(--hr-shadow-soft);
            min-width: 260px;
        }
        .color-popover__picker { width: 48px; height: 36px; border: 1px solid var(--hr-line); border-radius: 8px; background: var(--hr-surface); padding: 2px; }
        .preview-chip { display:inline-flex; align-items:center; justify-content:center; height:32px; min-width:48px; padding:0 10px; border-radius:8px; color:#fff; background:#000; font-weight:800; }

        /* Toast */
        .hr-toast { position: fixed; right: 16px; top: 16px; z-index: 3000; }
        .hr-toast-item { border-radius: 10px; padding: 10px 14px; font-weight: 700; border: 1px solid var(--hr-line); background: var(--hr-surface-strong); box-shadow: var(--hr-shadow-soft); }
        .hr-toast-success { color: #166534; background: rgb(34 197 94 / 0.1); border-color: rgb(34 197 94 / 0.36); }

        /* Confirm modal */
        .hr-modal-backdrop { position: fixed; inset: 0; background: rgb(2 8 23 / 0.55); z-index: var(--z-modal-overlay, 2200); display: none; }
        .hr-modal { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; z-index: var(--z-modal-content, 2210); }
        .hr-modal-panel { width: 100%; max-width: 420px; border: 1px solid var(--hr-line); background: var(--hr-surface-strong); border-radius: 14px; box-shadow: var(--hr-shadow-soft); padding: 16px; }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('themeStudioForm');
            const primaryText = document.querySelector('[data-brand-color-text="primary"]');
            const secondaryText = document.querySelector('[data-brand-color-text="secondary"]');
            const primaryPicker = document.querySelector('[data-brand-color-picker="primary"]');
            const secondaryPicker = document.querySelector('[data-brand-color-picker="secondary"]');
            const presetCards = document.querySelectorAll('[data-brand-preset]');
            const previewPane = document.getElementById('themePreview') || document.getElementById('themePreviewStandalone');
            const previewPaneStandalone = document.getElementById('themePreviewStandalone');
            const previewBtn = document.getElementById('previewChangesBtn');
            const previewSelectedBtn = document.getElementById('previewSelectedBtn');
            const popoverOpeners = document.querySelectorAll('[data-popover-open]');
            const popoverClosers = document.querySelectorAll('[data-popover-close]');
            const hexPattern = /^#[0-9A-F]{6}$/i;
            // Tabs
            const tabPresetsBtn = document.getElementById('tab_presets_btn');
            const tabCustomBtn = document.getElementById('tab_custom_btn');
            const tabPresets = document.getElementById('tab_presets');
            const tabCustom = document.getElementById('tab_custom');
            const toggleTabs = (show) => {
                const showPresets = show === 'presets';
                tabPresets.hidden = !showPresets;
                tabCustom.hidden = showPresets;
                tabPresetsBtn.setAttribute('aria-selected', showPresets ? 'true' : 'false');
                tabCustomBtn.setAttribute('aria-selected', showPresets ? 'false' : 'true');
            };
            tabPresetsBtn?.addEventListener('click', () => toggleTabs('presets'));
            tabCustomBtn?.addEventListener('click', () => toggleTabs('custom'));

            const normalizeHex = (value, fallback) => {
                const normalized = String(value || '').trim().toUpperCase();
                return hexPattern.test(normalized) ? normalized : fallback;
            };

            const hexToRgb = (hex) => {
                const v = hex.replace('#','');
                const r = parseInt(v.substring(0,2), 16);
                const g = parseInt(v.substring(2,4), 16);
                const b = parseInt(v.substring(4,6), 16);
                return { r, g, b };
            };

            const relativeLuminance = ({r,g,b}) => {
                const srgb = [r,g,b].map(v => v/255).map(v => v <= 0.03928 ? v/12.92 : Math.pow((v+0.055)/1.055, 2.4));
                return 0.2126*srgb[0] + 0.7152*srgb[1] + 0.0722*srgb[2];
            };

            const contrastRatio = (hexA, hexB) => {
                const L1 = relativeLuminance(hexToRgb(hexA));
                const L2 = relativeLuminance(hexToRgb(hexB));
                const [light, dark] = L1 >= L2 ? [L1, L2] : [L2, L1];
                const ratio = (light + 0.05) / (dark + 0.05);
                return Math.round(ratio * 100) / 100;
            };

            const setPreviewVars = () => {
                const p = normalizeHex(primaryText.value, '#7C3AED');
                const s = normalizeHex(secondaryText.value, '#5EEAD4');
                if (previewPane) {
                    previewPane.style.setProperty('--preview-primary', p);
                    previewPane.style.setProperty('--preview-secondary', s);
                }
                if (previewPaneStandalone) {
                    previewPaneStandalone.style.setProperty('--preview-primary', p);
                    previewPaneStandalone.style.setProperty('--preview-secondary', s);
                }
            };

            const applyPrimary = (value) => {
                const color = normalizeHex(value, '#7C3AED');
                primaryText.value = color;
                if (primaryPicker) primaryPicker.value = color;
                const rgbEl = document.querySelector('[data-rgb-display="primary"]');
                const contEl = document.querySelector('[data-contrast-display="primary"]');
                const chip = document.querySelector('[data-contrast-chip="primary"]');
                if (rgbEl && contEl && chip) {
                    const {r,g,b} = hexToRgb(color);
                    rgbEl.textContent = `${r}, ${g}, ${b}`;
                    const ratio = contrastRatio(color, '#FFFFFF');
                    contEl.textContent = `${ratio}:1` + (ratio < 4.5 ? ' • Low' : '');
                    chip.style.background = color;
                    chip.style.color = ratio < 4.5 ? '#111827' : '#fff';
                }
            };

            const applySecondary = (value) => {
                const color = normalizeHex(value, '#5EEAD4');
                secondaryText.value = color;
                if (secondaryPicker) secondaryPicker.value = color;
                const rgbEl = document.querySelector('[data-rgb-display="secondary"]');
                const contEl = document.querySelector('[data-contrast-display="secondary"]');
                const chip = document.querySelector('[data-contrast-chip="secondary"]');
                if (rgbEl && contEl && chip) {
                    const {r,g,b} = hexToRgb(color);
                    rgbEl.textContent = `${r}, ${g}, ${b}`;
                    const ratio = contrastRatio(color, '#FFFFFF');
                    contEl.textContent = `${ratio}:1` + (ratio < 4.5 ? ' • Low' : '');
                    chip.style.background = color;
                    chip.style.color = ratio < 4.5 ? '#111827' : '#fff';
                }
            };

            const setActivePreset = () => {
                const primaryValue = normalizeHex(primaryText.value, '#7C3AED');
                const secondaryValue = normalizeHex(secondaryText.value, '#5EEAD4');
                presetCards.forEach((card) => {
                    const presetPrimary = normalizeHex(card.dataset.brandPresetPrimary, '');
                    const presetSecondary = normalizeHex(card.dataset.brandPresetSecondary, '');
                    const isActive = presetPrimary === primaryValue && presetSecondary === secondaryValue;
                    card.classList.toggle('is-active', isActive);
                    card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
            };

            // Initialize
            applyPrimary(primaryText.value);
            applySecondary(secondaryText.value);
            setActivePreset();
            setPreviewVars();

            // Wiring
            if (primaryPicker) primaryPicker.addEventListener('input', () => { applyPrimary(primaryPicker.value); setActivePreset(); setPreviewVars(); });
            if (secondaryPicker) secondaryPicker.addEventListener('input', () => { applySecondary(secondaryPicker.value); setActivePreset(); setPreviewVars(); });
            primaryText.addEventListener('input', () => { if (hexPattern.test(primaryText.value.trim())) { applyPrimary(primaryText.value); setActivePreset(); setPreviewVars(); } });
            secondaryText.addEventListener('input', () => { if (hexPattern.test(secondaryText.value.trim())) { applySecondary(secondaryText.value); setActivePreset(); setPreviewVars(); } });
            primaryText.addEventListener('blur', () => { applyPrimary(primaryText.value); setPreviewVars(); });
            secondaryText.addEventListener('blur', () => { applySecondary(secondaryText.value); setPreviewVars(); });

            // Preset selection + pagination (2x2 grid, 4 per page)
            let presetPage = 1; const pageSize = 4; const totalPresets = presetCards.length; const totalPages = Math.max(1, Math.ceil(totalPresets / pageSize));
            const pageLabel = document.getElementById('presetsPageLabel');
            const prevBtn = document.getElementById('presetsPrev');
            const nextBtn = document.getElementById('presetsNext');
            const updatePage = () => {
                presetCards.forEach((card, idx) => {
                    const page = Math.floor(idx / pageSize) + 1;
                    const onPage = page === presetPage;
                    card.hidden = !onPage;
                });
                if (pageLabel) pageLabel.textContent = `Page ${presetPage} of ${totalPages}`;
                prevBtn?.setAttribute('disabled', presetPage <= 1 ? 'true' : 'false');
                nextBtn?.setAttribute('disabled', presetPage >= totalPages ? 'true' : 'false');
            };
            updatePage();
            prevBtn?.addEventListener('click', () => { if (presetPage > 1) { presetPage--; updatePage(); } });
            nextBtn?.addEventListener('click', () => { if (presetPage < totalPages) { presetPage++; updatePage(); } });

            presetCards.forEach((card) => {
                card.addEventListener('click', () => {
                    const pri = card.dataset.brandPresetPrimary;
                    const sec = card.dataset.brandPresetSecondary;
                    applyPrimary(pri);
                    applySecondary(sec);
                    setActivePreset();
                    setPreviewVars();
                });
            });

            // Font selection removed: Typography now has its own page

            // Color popovers
            const openPopover = (which) => {
                const el = document.getElementById(`popover_${which}`);
                if (!el) return;
                el.hidden = false;
                const opener = document.querySelector(`[data-popover-open="${which}"]`);
                if (opener) opener.setAttribute('aria-expanded', 'true');
            };
            const closePopover = (which) => {
                const el = document.getElementById(`popover_${which}`);
                if (!el) return;
                el.hidden = true;
                const opener = document.querySelector(`[data-popover-open="${which}"]`);
                if (opener) opener.setAttribute('aria-expanded', 'false');
            };
            popoverOpeners.forEach(btn => btn.addEventListener('click', () => openPopover(btn.dataset.popoverOpen)));
            popoverClosers.forEach(btn => btn.addEventListener('click', () => closePopover(btn.dataset.popoverClose)));
            // hex inputs inside popovers mirror main inputs
            document.querySelectorAll('[data-hex-input]')?.forEach((input) => {
                input.addEventListener('input', () => {
                    const which = input.dataset.hexInput;
                    if (which === 'primary') { applyPrimary(input.value); }
                    if (which === 'secondary') { applySecondary(input.value); }
                    setActivePreset(); setPreviewVars();
                });
            });

            // Preview buttons
            if (previewBtn) previewBtn.addEventListener('click', () => setPreviewVars());
            if (previewSelectedBtn) previewSelectedBtn.addEventListener('click', () => setPreviewVars());

            // Confirmation before apply
            const ensureConfirmModal = () => {
                if (document.getElementById('hrConfirmBackdrop')) return;
                const backdrop = document.createElement('div');
                backdrop.id = 'hrConfirmBackdrop';
                backdrop.className = 'hr-modal-backdrop';
                const modal = document.createElement('div');
                modal.id = 'hrConfirmModal';
                modal.className = 'hr-modal';
                modal.innerHTML = `
                <div class="hr-modal-panel">
                    <h3 class="text-base font-extrabold">Apply Global Theme?</h3>
                    <p class="text-sm mt-2" style="color: var(--hr-text-muted);">This updates the theme for all users. Proceed?</p>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="ui-btn ui-btn-ghost" id="hrConfirmCancel">Cancel</button>
                        <button type="button" class="ui-btn ui-btn-primary" id="hrConfirmOK">Apply</button>
                    </div>
                </div>`;
                document.body.appendChild(backdrop);
                document.body.appendChild(modal);
                const close = () => { backdrop.style.display = 'none'; modal.style.display = 'none'; };
                document.getElementById('hrConfirmCancel').addEventListener('click', close);
                backdrop.addEventListener('click', close);
                document.getElementById('hrConfirmOK').addEventListener('click', () => {
                    close();
                    form.submit();
                });
                modal._open = () => { backdrop.style.display = 'block'; modal.style.display = 'flex'; };
            };

            form.addEventListener('submit', (e) => {
                if (e.submitter && e.submitter.id === 'applyThemeBtn') {
                    e.preventDefault();
                    ensureConfirmModal();
                    document.getElementById('hrConfirmModal')._open();
                }
            });

            // Default to Presets tab on load
            toggleTabs('presets');

            // Mirror preview vars to standalone preview too
            if (previewPaneStandalone) {
                const applyPreviewVars = () => {
                    const p = normalizeHex(primaryText.value, '#7C3AED');
                    const s = normalizeHex(secondaryText.value, '#5EEAD4');
                    previewPaneStandalone.style.setProperty('--preview-primary', p);
                    previewPaneStandalone.style.setProperty('--preview-secondary', s);
                };
                applyPreviewVars();
                const mirror = () => { applyPreviewVars(); };
                [primaryText, secondaryText].forEach(el => {
                    el.addEventListener('input', mirror);
                    el.addEventListener('change', mirror);
                });
            }

            // Toast on success (enhanced alert)
            const status = @json(session('status'));
            if (status) {
                const wrap = document.createElement('div');
                wrap.className = 'hr-toast';
                const item = document.createElement('div');
                item.className = 'hr-toast-item hr-toast-success';
                item.textContent = status;
                wrap.appendChild(item);
                document.body.appendChild(wrap);
                setTimeout(() => { wrap.remove(); }, 4000);
            }
        })();
    </script>
@endpush
@endsection
