@props([
    'type' => 'empty',
    'title' => '目前沒有資料',
    'message' => '請稍後重試，或回到上一頁重新操作。',
    'actionLabel' => null,
    'actionHref' => null,
])

@php
    $stateClass = [
        'success' => 'rgba(34, 197, 94, 0.45)',
        'error' => 'rgba(244, 63, 94, 0.45)',
        'retry' => 'rgba(245, 158, 11, 0.45)',
        'loading' => 'rgba(59, 130, 246, 0.45)',
        'empty' => 'rgba(148, 163, 184, 0.45)',
    ][$type] ?? 'rgba(148, 163, 184, 0.45)';
@endphp

<div {{ $attributes->merge(['class' => 'vogue-card']) }} style="border-color: {{ $stateClass }};">
    <p style="color: var(--vogue-heading); font-weight: 700;">{{ $title }}</p>
    <p class="mt-2" style="color: var(--vogue-ink-soft);">{{ $message }}</p>

    @if ($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="vogue-btn vogue-btn-soft mt-4">
            {{ $actionLabel }}
        </a>
    @endif

    {{ $slot }}
</div>
