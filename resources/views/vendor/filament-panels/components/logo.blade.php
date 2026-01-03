@php
    $brandName = filament()->getBrandName();
    $brandLogo = filament()->getBrandLogo();
    // Default: 3rem everywhere; reduced to 1.5rem on mobile via CSS.
    $brandLogoHeight = filament()->getBrandLogoHeight() ?? '3rem';
    $darkModeBrandLogo = filament()->getDarkModeBrandLogo();
    $hasDarkModeBrandLogo = filled($darkModeBrandLogo);

    /**
     * Safety net:
     * In some Windows + ViteTheme setups we've seen the brand logo not resolving
     * even when configured in the panel provider. If Filament doesn't provide a
     * logo, fall back to our local SVG (keeps auth pages consistent).
     */
    if (blank($brandLogo) && file_exists(public_path('images/logo.svg'))) {
        $brandLogo = asset('images/logo.svg');
    }

    $getLogoClasses = fn (bool $isDarkMode): string => \Illuminate\Support\Arr::toCssClasses([
        'fi-logo',
        'fi-logo-light' => $hasDarkModeBrandLogo && (! $isDarkMode),
        'fi-logo-dark' => $isDarkMode,
    ]);

    $logoStyles = "height: {$brandLogoHeight}";
@endphp

@capture($content, $logo, $isDarkMode = false)
    @if ($logo instanceof \Illuminate\Contracts\Support\Htmlable)
        <div
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
        >
            {{ $logo }}
        </div>
    @elseif (filled($logo))
        <img
            alt="{{ __('filament-panels::layout.logo.alt', ['name' => $brandName]) }}"
            src="{{ $logo }}"
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
        />
    @else
        <div
            {{
                $attributes->class([
                    $getLogoClasses($isDarkMode),
                ])
            }}
        >
            {{ $brandName }}
        </div>
    @endif
@endcapture

{{ $content($brandLogo) }}

@if ($hasDarkModeBrandLogo)
    {{ $content($darkModeBrandLogo, isDarkMode: true) }}
@endif
