<x-vogue-page :title="'VogueAI | ' . $module['title']" skeleton-id="vogue-workspace-skeleton">
    <section class="vogue-highlight reveal mt-6">
        <div>
            <p class="vogue-eyebrow">Module Workspace</p>
            <h2>{{ $module['title'] }}</h2>
            <p>{{ $module['summary'] }}</p>
        </div>
        <div class="vogue-closet-toolbar">
            <span class="vogue-chip {{ str_contains($module['status'], 'degraded') ? 'vogue-chip-degraded' : 'vogue-chip-pending' }}">{{ $module['status'] }}</span>
            <a href="{{ route('closet.hub') }}" class="vogue-btn vogue-btn-soft">回 Smart Hub</a>
        </div>
    </section>

    <section class="vogue-section reveal">
        <div class="grid gap-4 lg:grid-cols-3">
            <article class="vogue-card">
                <p class="vogue-label">Primary Action</p>
                <h3>{{ $module['primaryAction'] }}</h3>
            </article>
            <article class="vogue-card">
                <p class="vogue-label">API Endpoint</p>
                <h3>{{ $module['api'] }}</h3>
            </article>
            <article class="vogue-card">
                <p class="vogue-label">Integration Status</p>
                <h3>{{ strtoupper($module['status']) }}</h3>
            </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-2 mt-4">
            <article class="vogue-card">
                <h3>輸入欄位（前端展示）</h3>
                <div class="mt-3 space-y-3">
                    @foreach ($module['fields'] as $field)
                        <div>
                            <label class="vogue-label">{{ $field }}</label>
                            <input type="text" class="vogue-input" placeholder="{{ $field }}" disabled>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="vogue-btn vogue-btn-solid mt-4" disabled>{{ $module['primaryAction'] }}</button>
            </article>

            <article class="vogue-card">
                <h3>最近任務（展示）</h3>
                <div class="mt-3 space-y-3">
                    <div class="vogue-card" style="padding: 0.85rem;">
                        <p class="vogue-label">JOB-2401</p>
                        <p style="color: var(--vogue-heading);">status: {{ $module['status'] }}</p>
                        <p style="color: var(--vogue-ink-soft);">module: {{ $module['slug'] }}</p>
                    </div>
                    <div class="vogue-card" style="padding: 0.85rem;">
                        <p class="vogue-label">JOB-2402</p>
                        <p style="color: var(--vogue-heading);">status: pending</p>
                        <p style="color: var(--vogue-ink-soft);">module: {{ $module['slug'] }}</p>
                    </div>
                </div>
            </article>
        </div>

        <article class="vogue-card mt-4">
            <h3>快速切換</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($modules as $item)
                    <a href="{{ route('workspace.show', $item['slug']) }}" class="vogue-btn {{ $module['slug'] === $item['slug'] ? 'vogue-btn-solid' : 'vogue-btn-soft' }}">{{ $item['title'] }}</a>
                @endforeach
            </div>
        </article>
    </section>
</x-vogue-page>
