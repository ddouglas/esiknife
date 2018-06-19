<h4 class="text-center">Navigation</h4>
<div class="list-group">
    <a href="{{ route('mails') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        All Mail
        <span class="badge badge-primary badge-pill">{{ $member->labels->total_unread_count }}</span>
    </a>
    @foreach ($labels as $label)
        <a href="{{ route('mail', ['label' => $label->get('label_id')]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            {{ $label->get('name') }}
            <span class="badge badge-primary badge-pill">{{ $label->get('unread_count') }}</span>
        </a>
    @endforeach
</div>
@if ($lists->isNotEmpty())
    <hr />
    <h4 class="text-center">Mailing Lists</h4>
    <div class="list-group">
        @foreach ($lists as $list)
            <a href="{{ route('mail', ['ml' => $list->id]) }}" class="list-group-item list-group-item-action">{{ $list->name }}</a>
        @endforeach
    </div>
@endif
