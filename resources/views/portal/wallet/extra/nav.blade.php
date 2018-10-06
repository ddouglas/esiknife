<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item ml-2">
                <a class="nav-link {{ $currentRouteName === 'transactions' ? 'active' : null }}" href="{{ route('transactions', ['member' => $member->id]) }}">Wallet</a>
            </li>
            <li class="nav-item ml-2">
                <a class="nav-link {{ $currentRouteName === 'journal' ? 'active' : null }}" href="{{ route('journal', ['member' => $member->id]) }}">Journal</a>
            </li>
        </ul>
        <hr />
    </div>
</div>
