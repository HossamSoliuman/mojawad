<div>
    <div class="card-select" style="margin-bottom:1.2rem">
        @foreach([
            '' => ['label' => __('All'), 'icon' => 'fa-list'],
            'submitted' => ['label' => __('Submitted'), 'icon' => 'fa-paper-plane'],
            'resubmitted' => ['label' => __('Resubmitted'), 'icon' => 'fa-rotate'],
            'approved' => ['label' => __('Approved'), 'icon' => 'fa-check'],
            'rejected' => ['label' => __('Rejected'), 'icon' => 'fa-xmark'],
        ] as $value => $opt)
            <button type="button" class="card-opt" wire:click="$set('action', '{{ $value }}')" wire:key="filter-{{ $value ?: 'all' }}">
                <span class="card-opt-inner" @class(['card-opt-active' => $action === $value])>
                    <i class="fas {{ $opt['icon'] }}"></i>
                    <span>{{ $opt['label'] }}</span>
                    @if($action === $value)<i class="fas fa-circle-check" style="margin-left:auto;color:var(--gold)"></i>@endif
                </span>
            </button>
        @endforeach
    </div>

    <div class="tbl-wrap">
        <table class="tbl tbl-stack">
            <thead><tr><th>{{ __('Tilawa') }}</th><th>{{ __('Action') }}</th><th>{{ __('Reviewer') }}</th><th>{{ __('Note') }}</th><th>{{ __('When') }}</th></tr></thead>
            <tbody>
                @forelse($reviews as $r)
                    <tr wire:key="rev-{{ $r->id }}">
                        <td data-label="{{ __('Tilawa') }}">
                            <span style="font-weight:600;font-size:.86rem">{{ $r->tilawa?->title ?? '—' }}</span>
                            @if($r->tilawa?->qari)
                                <div style="font-size:.76rem;color:var(--gold)">{{ $r->tilawa->qari->name }}</div>
                            @endif
                        </td>
                        <td data-label="{{ __('Action') }}">
                            <span class="badge {{ ['submitted'=>'badge-muted','resubmitted'=>'badge-blue','approved'=>'badge-green','rejected'=>'badge-red'][$r->action] ?? 'badge-muted' }}">{{ __($r->action) }}</span>
                        </td>
                        <td data-label="{{ __('Reviewer') }}" style="font-size:.82rem;color:var(--text2)">{{ $r->reviewer?->name ?? '—' }}</td>
                        <td data-label="{{ __('Note') }}" style="font-size:.82rem;color:var(--text2);max-width:320px">{{ $r->note ?? '—' }}</td>
                        <td data-label="{{ __('When') }}" style="font-size:.78rem;color:var(--text2)">{{ $r->created_at->format('d M Y · H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:3rem;color:var(--text2)">{{ __('No review history yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $reviews->links('vendor.pagination.custom') }}</div>
</div>
