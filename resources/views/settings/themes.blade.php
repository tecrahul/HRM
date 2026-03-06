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

        <form id="themeStudioForm" method="POST" action="{{ route('settings.themes.update') }}" enctype="multipart/form-data" class="mt-5">
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
                $currentLightBg = strtoupper((string) old('light_bg_color', $companySettings['light_bg_color'] ?? '#F5F5F5'));
                if (! preg_match('/^#[0-9A-F]{6}$/', $currentLightBg)) {
                    $currentLightBg = '#F5F5F5';
                }
                $currentLightSidebar = strtoupper((string) old('light_sidebar_color', $companySettings['light_sidebar_color'] ?? '#FFFFFF'));
                if (! preg_match('/^#[0-9A-F]{6}$/', $currentLightSidebar)) {
                    $currentLightSidebar = '#FFFFFF';
                }
                $currentLightHeader = strtoupper((string) old('light_header_color', $companySettings['light_header_color'] ?? '#FFFFFF'));
                if (! preg_match('/^#[0-9A-F]{6}$/', $currentLightHeader)) {
                    $currentLightHeader = '#FFFFFF';
                }
                $brandColorPresets = [
                    // Page 1 - Popular & Classic
                    ['name' => 'Violet + Mint', 'primary' => '#7C3AED', 'secondary' => '#5EEAD4'],
                    ['name' => 'Indigo + Amber', 'primary' => '#4F46E5', 'secondary' => '#F59E0B'],
                    ['name' => 'Emerald + Slate', 'primary' => '#059669', 'secondary' => '#334155'],
                    ['name' => 'Rose + Navy', 'primary' => '#E11D48', 'secondary' => '#1E3A8A'],
                    ['name' => 'Ocean Blue', 'primary' => '#0EA5E9', 'secondary' => '#0F172A'],
                    ['name' => 'Royal Purple', 'primary' => '#7E22CE', 'secondary' => '#FCD34D'],
                    ['name' => 'Forest Green', 'primary' => '#166534', 'secondary' => '#FEF3C7'],
                    ['name' => 'Coral Reef', 'primary' => '#F43F5E', 'secondary' => '#22D3EE'],
                    ['name' => 'Golden Hour', 'primary' => '#D97706', 'secondary' => '#312E81'],
                    ['name' => 'Arctic Blue', 'primary' => '#0284C7', 'secondary' => '#F1F5F9'],
                    ['name' => 'Berry Crush', 'primary' => '#BE185D', 'secondary' => '#A5F3FC'],
                    ['name' => 'Olive Garden', 'primary' => '#65A30D', 'secondary' => '#1E293B'],

                    // Page 2 - Enterprise & Professional
                    ['name' => 'Corporate Blue', 'primary' => '#1D4ED8', 'secondary' => '#E5E7EB'],
                    ['name' => 'Executive Gray', 'primary' => '#374151', 'secondary' => '#10B981'],
                    ['name' => 'Trust Green', 'primary' => '#047857', 'secondary' => '#F3F4F6'],
                    ['name' => 'Midnight + Teal', 'primary' => '#0F172A', 'secondary' => '#14B8A6'],
                    ['name' => 'Graphite + Lime', 'primary' => '#1F2937', 'secondary' => '#84CC16'],
                    ['name' => 'Cobalt + Coral', 'primary' => '#2563EB', 'secondary' => '#F97316'],
                    ['name' => 'Navy + Gold', 'primary' => '#1E3A8A', 'secondary' => '#FBBF24'],
                    ['name' => 'Charcoal + Cyan', 'primary' => '#18181B', 'secondary' => '#06B6D4'],
                    ['name' => 'Slate + Orange', 'primary' => '#475569', 'secondary' => '#FB923C'],
                    ['name' => 'Steel + Emerald', 'primary' => '#64748B', 'secondary' => '#34D399'],
                    ['name' => 'Onyx + Rose', 'primary' => '#27272A', 'secondary' => '#FB7185'],
                    ['name' => 'Pewter + Sky', 'primary' => '#52525B', 'secondary' => '#38BDF8'],

                    // Page 3 - Modern & Vibrant
                    ['name' => 'Electric Violet', 'primary' => '#8B5CF6', 'secondary' => '#F472B6'],
                    ['name' => 'Neon Sunset', 'primary' => '#EC4899', 'secondary' => '#FBBF24'],
                    ['name' => 'Cyber Teal', 'primary' => '#0D9488', 'secondary' => '#A78BFA'],
                    ['name' => 'Aurora', 'primary' => '#6366F1', 'secondary' => '#34D399'],
                    ['name' => 'Sunset Glow', 'primary' => '#F59E0B', 'secondary' => '#7C3AED'],
                    ['name' => 'Mint Fresh', 'primary' => '#10B981', 'secondary' => '#F43F5E'],
                    ['name' => 'Cherry Blossom', 'primary' => '#DB2777', 'secondary' => '#86EFAC'],
                    ['name' => 'Deep Ocean', 'primary' => '#0369A1', 'secondary' => '#FDE68A'],
                    ['name' => 'Lavender Fields', 'primary' => '#A855F7', 'secondary' => '#FCD34D'],
                    ['name' => 'Rustic Orange', 'primary' => '#EA580C', 'secondary' => '#1F2937'],
                    ['name' => 'Pine Forest', 'primary' => '#15803D', 'secondary' => '#FECACA'],
                    ['name' => 'Sapphire + Pearl', 'primary' => '#2563EB', 'secondary' => '#F8FAFC'],
                ];
            @endphp

            <!-- Tabs and content -->
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
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <h4 class="text-sm font-extrabold">Theme Presets</h4>
                            <p class="text-xs mt-0.5" style="color: var(--hr-text-muted);">Choose from 36 professionally designed color combinations</p>
                        </div>
                        <div class="flex items-center gap-2" id="presetsPagination" aria-label="Presets pages">
                            <button type="button" class="h-8 w-8 rounded-lg flex items-center justify-center border transition-colors hover:bg-[var(--hr-surface)]" id="presetsPrev" aria-label="Previous page" style="border-color: var(--hr-line);">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <span class="text-xs font-semibold min-w-[80px] text-center" id="presetsPageLabel" style="color: var(--hr-text-muted);">Page 1 of 3</span>
                            <button type="button" class="h-8 w-8 rounded-lg flex items-center justify-center border transition-colors hover:bg-[var(--hr-surface)]" id="presetsNext" aria-label="Next page" style="border-color: var(--hr-line);">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Presets Grid (2 columns) -->
                        <div class="lg:col-span-2 space-y-4">
                            <div id="presetsGrid" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach ($brandColorPresets as $index => $preset)
                                    <button type="button"
                                        class="theme-card group relative rounded-xl border p-3 flex flex-col gap-2 text-left transition-all"
                                        style="border-color: var(--hr-line); background: var(--hr-surface);"
                                        data-brand-preset
                                        data-index="{{ $index }}"
                                        data-brand-preset-primary="{{ $preset['primary'] }}"
                                        data-brand-preset-secondary="{{ $preset['secondary'] }}"
                                        aria-pressed="false"
                                    >
                                        <span class="selected-badge" aria-hidden="true">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                                        </span>
                                        <div class="rounded-lg overflow-hidden border aspect-[2/1]" style="border-color: var(--hr-line)">
                                            <div class="h-full w-full" style="background: linear-gradient(135deg, {{ $preset['primary'] }} 0%, {{ $preset['primary'] }} 50%, {{ $preset['secondary'] }} 50%, {{ $preset['secondary'] }} 100%);"></div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold truncate">{{ $preset['name'] }}</p>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="h-3 w-3 rounded-full border" style="background: {{ $preset['primary'] }}; border-color: rgba(0,0,0,0.1);"></span>
                                                <span class="h-3 w-3 rounded-full border" style="background: {{ $preset['secondary'] }}; border-color: rgba(0,0,0,0.1);"></span>
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                            <div class="flex items-center justify-between gap-3 pt-4 border-t" style="border-color: var(--hr-line);">
                                <div class="flex items-center gap-2">
                                    <button type="button" id="page1Btn" class="page-dot h-2 w-2 rounded-full transition-all" style="background: var(--hr-accent);" data-page="1"></button>
                                    <button type="button" id="page2Btn" class="page-dot h-2 w-2 rounded-full transition-all" style="background: var(--hr-line);" data-page="2"></button>
                                    <button type="button" id="page3Btn" class="page-dot h-2 w-2 rounded-full transition-all" style="background: var(--hr-line);" data-page="3"></button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" class="ui-btn ui-btn-primary" id="applyThemeBtn">Apply Theme</button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Live Preview -->
                        <div class="space-y-4">
                            <div id="themePreviewStandalone" class="rounded-xl border overflow-hidden" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                <div class="px-4 py-3 border-b flex items-center justify-between" style="border-color: var(--hr-line);">
                                    <h5 class="text-xs font-bold uppercase tracking-wider" style="color: var(--hr-text-muted);">Live Preview</h5>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background: var(--hr-accent-soft); color: var(--hr-accent);">Unsaved</span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <!-- Mini Dashboard Preview -->
                                    <div class="rounded-lg border p-3" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="h-8 w-8 rounded-lg flex items-center justify-center" style="background: color-mix(in oklab, var(--preview-primary, var(--hr-accent)), #fff 85%);">
                                                <svg class="h-4 w-4" style="color: var(--preview-primary, var(--hr-accent));" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold">Dashboard Card</p>
                                                <p class="text-[11px]" style="color: var(--hr-text-muted);">Sample content preview</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold text-white" style="background: var(--preview-primary, var(--hr-accent));">Primary Action</button>
                                            <button type="button" class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold border" style="border-color: var(--hr-line);">Secondary</button>
                                        </div>
                                    </div>

                                    <!-- Color Swatches -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-lg p-3 text-center" style="background: var(--preview-primary, var(--hr-accent));">
                                            <p class="text-xs font-bold text-white">Primary</p>
                                        </div>
                                        <div class="rounded-lg p-3 text-center" style="background: var(--preview-secondary, #5EEAD4);">
                                            <p class="text-xs font-bold" style="color: var(--hr-text-main);">Secondary</p>
                                        </div>
                                    </div>

                                    <!-- Badges & Tags -->
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: color-mix(in oklab, var(--preview-primary, var(--hr-accent)), #fff 85%); color: var(--preview-primary, var(--hr-accent));">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                            Active
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: var(--hr-surface-strong); border: 1px solid var(--hr-line);">Pending</span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: color-mix(in oklab, var(--preview-secondary, #5EEAD4), #fff 75%); color: #0f766e;">New</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Custom tab panel -->
                <section id="tab_custom" role="tabpanel" aria-labelledby="tab_custom_btn" class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface-strong);" hidden>
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <h4 class="text-sm font-extrabold">Custom Theme</h4>
                            <p class="text-xs mt-0.5" style="color: var(--hr-text-muted);">Create your own unique color scheme</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left: Color Controls -->
                        <div class="space-y-5">
                            <!-- Brand Colors Section -->
                            <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                <h5 class="text-xs font-bold uppercase tracking-wider mb-4" style="color: var(--hr-text-muted);">Brand Colors</h5>

                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Primary Color -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold" style="color: var(--hr-text-muted);">Primary</label>
                                        <div class="flex items-center gap-2">
                                            <div class="relative flex-1">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 rounded-md border" id="primaryColorSwatch" style="background: {{ $currentPrimaryColor }}; border-color: rgba(0,0,0,0.1);"></span>
                                                <input id="brand_primary_color" name="brand_primary_color" type="text" value="{{ $currentPrimaryColor }}" class="w-full rounded-lg border pl-10 pr-3 py-2 text-sm font-mono bg-transparent" style="border-color: var(--hr-line);" placeholder="#7C3AED" data-brand-color-text="primary">
                                            </div>
                                            <input id="brand_primary_color_picker" type="color" value="{{ $currentPrimaryColor }}" class="h-9 w-9 rounded-lg border cursor-pointer p-0.5" style="border-color: var(--hr-line);" data-brand-color-picker="primary" aria-label="Pick primary color">
                                        </div>
                                        <p class="text-[10px]" style="color: var(--hr-text-muted);">Used for buttons & highlights</p>
                                        @error('brand_primary_color')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Secondary Color -->
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold" style="color: var(--hr-text-muted);">Secondary</label>
                                        <div class="flex items-center gap-2">
                                            <div class="relative flex-1">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 rounded-md border" id="secondaryColorSwatch" style="background: {{ $currentSecondaryColor }}; border-color: rgba(0,0,0,0.1);"></span>
                                                <input id="brand_secondary_color" name="brand_secondary_color" type="text" value="{{ $currentSecondaryColor }}" class="w-full rounded-lg border pl-10 pr-3 py-2 text-sm font-mono bg-transparent" style="border-color: var(--hr-line);" placeholder="#5EEAD4" data-brand-color-text="secondary">
                                            </div>
                                            <input id="brand_secondary_color_picker" type="color" value="{{ $currentSecondaryColor }}" class="h-9 w-9 rounded-lg border cursor-pointer p-0.5" style="border-color: var(--hr-line);" data-brand-color-picker="secondary" aria-label="Pick secondary color">
                                        </div>
                                        <p class="text-[10px]" style="color: var(--hr-text-muted);">Accent & complementary</p>
                                        @error('brand_secondary_color')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Color Preview Bar -->
                                <div class="mt-4 rounded-lg overflow-hidden border" style="border-color: var(--hr-line);">
                                    <div class="h-10 w-full flex">
                                        <div class="flex-1" id="previewPrimaryBar" style="background: {{ $currentPrimaryColor }};"></div>
                                        <div class="flex-1" id="previewSecondaryBar" style="background: {{ $currentSecondaryColor }};"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Interface Colors Section -->
                            <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                <h5 class="text-xs font-bold uppercase tracking-wider mb-4" style="color: var(--hr-text-muted);">Interface Colors (Light Mode)</h5>

                                <div class="grid grid-cols-3 gap-3">
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-semibold" style="color: var(--hr-text-muted);">Background</label>
                                        <div class="flex items-center gap-1.5">
                                            <input type="color" value="{{ $currentLightBg }}" onchange="document.getElementById('light_bg_color').value = this.value.toUpperCase();" class="h-8 w-8 rounded-md border cursor-pointer p-0.5" style="border-color: var(--hr-line);">
                                            <input id="light_bg_color" name="light_bg_color" type="text" value="{{ $currentLightBg }}" class="w-full rounded-md border px-2 py-1.5 text-xs font-mono bg-transparent" style="border-color: var(--hr-line);" placeholder="#F5F5F5">
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-semibold" style="color: var(--hr-text-muted);">Sidebar</label>
                                        <div class="flex items-center gap-1.5">
                                            <input type="color" value="{{ $currentLightSidebar }}" onchange="document.getElementById('light_sidebar_color').value = this.value.toUpperCase();" class="h-8 w-8 rounded-md border cursor-pointer p-0.5" style="border-color: var(--hr-line);">
                                            <input id="light_sidebar_color" name="light_sidebar_color" type="text" value="{{ $currentLightSidebar }}" class="w-full rounded-md border px-2 py-1.5 text-xs font-mono bg-transparent" style="border-color: var(--hr-line);" placeholder="#FFFFFF">
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[11px] font-semibold" style="color: var(--hr-text-muted);">Header</label>
                                        <div class="flex items-center gap-1.5">
                                            <input type="color" value="{{ $currentLightHeader }}" onchange="document.getElementById('light_header_color').value = this.value.toUpperCase();" class="h-8 w-8 rounded-md border cursor-pointer p-0.5" style="border-color: var(--hr-line);">
                                            <input id="light_header_color" name="light_header_color" type="text" value="{{ $currentLightHeader }}" class="w-full rounded-md border px-2 py-1.5 text-xs font-mono bg-transparent" style="border-color: var(--hr-line);" placeholder="#FFFFFF">
                                        </div>
                                    </div>
                                </div>
                                @error('light_bg_color')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
                                @error('light_sidebar_color')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
                                @error('light_header_color')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
                            </div>

                            <!-- Background Image (Optional) -->
                            <div class="rounded-xl border p-4" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                <h5 class="text-xs font-bold uppercase tracking-wider mb-3" style="color: var(--hr-text-muted);">Background Image (Optional)</h5>
                                <input type="file" name="light_bg_image" accept="image/*" class="w-full rounded-lg border px-3 py-2 text-sm bg-transparent" style="border-color: var(--hr-line);">
                                @if (!empty($companySettings['light_bg_image_path']))
                                    <div class="mt-3 flex items-center gap-3">
                                        <img src="{{ asset('storage/' . $companySettings['light_bg_image_path']) }}" alt="Background preview" class="h-12 w-20 object-cover rounded-lg border" style="border-color: var(--hr-line);">
                                        <label class="inline-flex items-center gap-2 text-xs cursor-pointer" style="color: var(--hr-text-muted);"><input type="checkbox" name="remove_light_bg_image" value="1" class="rounded"> Remove</label>
                                    </div>
                                @endif
                                @error('light_bg_image')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Right: Live Preview -->
                        <div class="space-y-4">
                            <div id="themePreview" class="rounded-xl border overflow-hidden" style="border-color: var(--hr-line); background: var(--hr-surface-strong);">
                                <div class="px-4 py-3 border-b flex items-center justify-between" style="border-color: var(--hr-line);">
                                    <h5 class="text-xs font-bold uppercase tracking-wider" style="color: var(--hr-text-muted);">Live Preview</h5>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background: var(--hr-accent-soft); color: var(--hr-accent);">Unsaved</span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <!-- Mini Dashboard Preview -->
                                    <div class="rounded-lg border p-3" style="border-color: var(--hr-line); background: var(--hr-surface);">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="h-8 w-8 rounded-lg flex items-center justify-center" id="previewIconBg" style="background: var(--preview-primary, var(--hr-accent)); opacity: 0.15;">
                                                <svg class="h-4 w-4" id="previewIcon" style="color: var(--preview-primary, var(--hr-accent));" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-5 7 5v13"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold">Dashboard Card</p>
                                                <p class="text-[11px]" style="color: var(--hr-text-muted);">Sample content preview</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold text-white" id="previewPrimaryBtn" style="background: var(--preview-primary, var(--hr-accent));">Primary Action</button>
                                            <button type="button" class="flex-1 rounded-lg px-3 py-2 text-xs font-semibold border" style="border-color: var(--hr-line);">Secondary</button>
                                        </div>
                                    </div>

                                    <!-- Color Swatches -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-lg p-3 text-center" id="previewPrimarySwatch" style="background: var(--preview-primary, var(--hr-accent));">
                                            <p class="text-xs font-bold text-white">Primary</p>
                                        </div>
                                        <div class="rounded-lg p-3 text-center" id="previewSecondarySwatch" style="background: var(--preview-secondary, var(--hr-accent-soft));">
                                            <p class="text-xs font-bold" style="color: var(--hr-text-main);">Secondary</p>
                                        </div>
                                    </div>

                                    <!-- Badges & Tags -->
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold" id="previewBadge1" style="background: color-mix(in oklab, var(--preview-primary, var(--hr-accent)), #fff 85%); color: var(--preview-primary, var(--hr-accent));">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                            Active
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: var(--hr-surface); border: 1px solid var(--hr-line);">Pending</span>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold" id="previewBadge2" style="background: color-mix(in oklab, var(--preview-secondary, #5EEAD4), #fff 75%); color: #0f766e;">New</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                <button type="button" class="ui-btn ui-btn-secondary flex-1" id="previewChangesBtn">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Preview
                                </button>
                                <button type="submit" class="ui-btn ui-btn-primary flex-1" id="applyCustomThemeBtn">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                    Apply Theme
                                </button>
                            </div>
                            <button type="reset" class="w-full text-xs text-center py-2" style="color: var(--hr-text-muted);">Reset to defaults</button>
                        </div>
                    </div>
                </section>
            </div>

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
        .theme-card:hover { transform: translateY(-2px); border-color: var(--hr-accent-border); box-shadow: 0 4px 12px -4px rgba(0,0,0,0.15); }
        .theme-card.is-active { border-color: var(--hr-accent); box-shadow: 0 0 0 2px var(--hr-accent-border), 0 8px 20px -8px color-mix(in oklab, var(--hr-accent), #000 30%); }
        .selected-badge {
            position: absolute; top: 6px; right: 6px; display: none;
            width: 18px; height: 18px; border-radius: 50%;
            background: var(--hr-accent); color: white;
            align-items: center; justify-content: center;
        }
        .theme-card.is-active .selected-badge { display: flex; }
        .page-dot { cursor: pointer; transition: all 150ms ease; }
        .page-dot:hover { transform: scale(1.3); }
        .page-dot.is-active { background: var(--hr-accent) !important; transform: scale(1.2); }

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
            const previewPane = document.getElementById('themePreview');
            const previewPanePresets = document.getElementById('themePreviewStandalone');
            const previewBtn = document.getElementById('previewChangesBtn');
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
                if (previewPanePresets) {
                    previewPanePresets.style.setProperty('--preview-primary', p);
                    previewPanePresets.style.setProperty('--preview-secondary', s);
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

            // Preset selection + pagination (12 per page, 3 pages)
            let presetPage = 1; const pageSize = 12; const totalPresets = presetCards.length; const totalPages = Math.max(1, Math.ceil(totalPresets / pageSize));
            const pageLabel = document.getElementById('presetsPageLabel');
            const prevBtn = document.getElementById('presetsPrev');
            const nextBtn = document.getElementById('presetsNext');
            const pageDots = document.querySelectorAll('.page-dot');
            const updatePage = () => {
                presetCards.forEach((card, idx) => {
                    const page = Math.floor(idx / pageSize) + 1;
                    const onPage = page === presetPage;
                    card.hidden = !onPage;
                });
                if (pageLabel) pageLabel.textContent = `Page ${presetPage} of ${totalPages}`;
                prevBtn?.style.setProperty('opacity', presetPage <= 1 ? '0.4' : '1');
                prevBtn?.style.setProperty('pointer-events', presetPage <= 1 ? 'none' : 'auto');
                nextBtn?.style.setProperty('opacity', presetPage >= totalPages ? '0.4' : '1');
                nextBtn?.style.setProperty('pointer-events', presetPage >= totalPages ? 'none' : 'auto');
                // Update page dots
                pageDots.forEach((dot, idx) => {
                    dot.classList.toggle('is-active', idx + 1 === presetPage);
                    dot.style.background = idx + 1 === presetPage ? 'var(--hr-accent)' : 'var(--hr-line)';
                });
            };
            updatePage();
            prevBtn?.addEventListener('click', () => { if (presetPage > 1) { presetPage--; updatePage(); } });
            nextBtn?.addEventListener('click', () => { if (presetPage < totalPages) { presetPage++; updatePage(); } });
            pageDots.forEach((dot) => {
                dot.addEventListener('click', () => {
                    const page = parseInt(dot.dataset.page, 10);
                    if (page && page !== presetPage) { presetPage = page; updatePage(); }
                });
            });

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

            // Preview button (Custom Theme tab)
            if (previewBtn) previewBtn.addEventListener('click', () => setPreviewVars());

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
