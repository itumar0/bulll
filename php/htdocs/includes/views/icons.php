<?php
defined('APP') or exit;

/** Inline SVG icon (decorative, aria-hidden). */
function icon(string $name, string $class = 'h-5 w-5'): string
{
    $s = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" class="' . e($class) . '"';
    $stroke = 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
    switch ($name) {
        case 'whatsapp':
            return "<svg $s fill=\"currentColor\"><path d=\"M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.3 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35ZM12.04 21.5h-.01a9.4 9.4 0 0 1-4.8-1.32l-.34-.2-3.57.94.95-3.48-.22-.36a9.43 9.43 0 1 1 8 4.42Zm8.02-17.47A11.3 11.3 0 0 0 12.04.7C5.8.7.72 5.78.72 12.02c0 2 .52 3.94 1.51 5.65L.62 23.5l5.98-1.57a11.3 11.3 0 0 0 5.43 1.38h.01c6.24 0 11.32-5.08 11.32-11.32 0-3.02-1.18-5.87-3.3-8Z\"/></svg>";
        case 'arrow':
            return "<svg $s fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M5 12h14M13 6l6 6-6 6\"/></svg>";
        case 'clock':
            return "<svg $s $stroke><circle cx=\"12\" cy=\"12\" r=\"9\"/><path d=\"M12 7v5l3 2\"/></svg>";
        case 'pin':
            return "<svg $s $stroke><path d=\"M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z\"/><circle cx=\"12\" cy=\"9.5\" r=\"2.5\"/></svg>";
        case 'phone':
            return "<svg $s $stroke><path d=\"M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z\"/></svg>";
        case 'users':
            return "<svg $s $stroke><path d=\"M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2\"/><circle cx=\"9\" cy=\"7\" r=\"4\"/><path d=\"M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8\"/></svg>";
        case 'check':
            return "<svg $s fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 6 9 17l-5-5\"/></svg>";
        case 'close':
            return "<svg $s fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"><path d=\"M18 6 6 18M6 6l12 12\"/></svg>";
        case 'crown':
            return "<svg $s $stroke><path d=\"m3 7 4.5 4L12 4l4.5 7L21 7l-2 12H5L3 7Z\"/></svg>";
        case 'instagram':
            return "<svg $s fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\"><rect x=\"3\" y=\"3\" width=\"18\" height=\"18\" rx=\"5\"/><circle cx=\"12\" cy=\"12\" r=\"4\"/><circle cx=\"17.5\" cy=\"6.5\" r=\"1\" fill=\"currentColor\" stroke=\"none\"/></svg>";
        case 'target':
            return "<svg $s fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\"><circle cx=\"12\" cy=\"12\" r=\"9\"/><circle cx=\"12\" cy=\"12\" r=\"5\"/><circle cx=\"12\" cy=\"12\" r=\"1.5\" fill=\"currentColor\" stroke=\"none\"/></svg>";
        case 'bolt':
            return "<svg $s $stroke><path d=\"M13 2 4 14h7l-1 8 9-12h-7l1-8Z\"/></svg>";
        case 'map':
            return "<svg $s $stroke><path d=\"m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z\"/><path d=\"M9 4v14M15 6v14\"/></svg>";
    }
    return '';
}

function logo_html(): string
{
    return '<a href="' . e(base_path()) . '" class="group flex items-center gap-2.5">'
        . '<img src="' . e(logo_url('logo-96')) . '" alt="" width="40" height="40" class="h-10 w-10 rounded-full shadow-glow-sm ring-1 ring-white/10 transition-transform group-hover:rotate-6">'
        . '<span class="text-lg font-extrabold tracking-tight">Бильярд <span class="text-accent-500">Старт</span><span class="sr-only"> — на главную</span></span></a>';
}

function btn_class(string $variant = 'primary', string $size = 'md', string $extra = ''): string
{
    $variants = [
        'primary' => 'bg-accent-600 text-white hover:bg-accent-700 shadow-glow hover:shadow-glow-lg',
        'outline' => 'border border-white/15 text-white hover:border-white/40 hover:bg-white/5',
        'ghost' => 'text-soft hover:text-white hover:bg-white/5',
        'whatsapp' => 'border border-white/15 text-white hover:border-[#25D366]/70 hover:bg-[#25D366]/10',
    ];
    $sizes = [
        'sm' => 'h-9 px-3.5 text-sm rounded-lg gap-1.5',
        'md' => 'h-12 px-5 text-[15px] rounded-xl gap-2',
        'lg' => 'h-14 px-7 text-base rounded-2xl gap-2.5',
    ];
    return 'inline-flex items-center justify-center font-semibold tracking-tight transition-all duration-200 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 '
        . $variants[$variant] . ' ' . $sizes[$size] . ' ' . $extra;
}

function section_heading(string $id, string $eyebrow, string $title, string $text = ''): string
{
    return '<div class="reveal mb-10 max-w-2xl sm:mb-14">'
        . '<p class="mb-3 text-xs font-bold tracking-[0.25em] text-accent-500 uppercase">' . e($eyebrow) . '</p>'
        . '<h2 id="' . e($id) . '" class="text-3xl leading-[1.1] font-extrabold tracking-tight sm:text-5xl">' . e($title) . '</h2>'
        . ($text !== '' ? '<p class="mt-4 text-base leading-relaxed text-mute sm:text-lg">' . e($text) . '</p>' : '')
        . '</div>';
}
