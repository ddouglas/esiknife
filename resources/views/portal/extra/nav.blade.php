<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item ml-2">
                <a class="nav-link {{ $currentRouteName === 'dashboard' ? 'active' : null }}" href="{{ route('dashboard') }}">Overview</a>
            </li>

            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterAssets')))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'assets' ? 'active' : null }}" href="{{ route('assets') }}">Assets</a>
                </li>
            @endif

            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterBookmarks')))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'bookmarks' ? 'active' : null }}" href="{{ route('bookmarks') }}">Bookmarks</a>
                </li>
            @endif

            @if (isset($scopes) && ($scopes->contains(config('services.eve.scopes.readCharacterClones')) || $scopes->contains(config('services.eve.scopes.readCharacterImplants')) ))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'clones' ? 'active' : null }}" href="{{ route('clones') }}">Clones & Implants</a>
                </li>
            @endif

            @if (isset($scopes) && ($scopes->contains(config('services.eve.scopes.readCharacterContacts'))))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'contacts' ? 'active' : null }}" href="{{ route('contacts') }}">Contacts</a>
                </li>
            @endif

            @if (isset($scopes) && ($scopes->contains(config('services.eve.scopes.readCharacterMails'))))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'mails' ? 'active' : null }}" href="{{ route('mails') }}">Evemail</a>
                </li>
            @endif

            @if (isset($scopes) && ($scopes->contains(config('services.eve.scopes.readCharacterContracts'))))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'contracts' ? 'active' : null }}" href="{{ route('contracts') }}">Contracts</a>
                </li>
            @endif

            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ in_array($currentRouteName, ['skillz', 'skillz.flyable']) ? 'active' : null }}" href="{{ route('skillz') }}">Skills</a>
                </li>
            @endif

            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkillQueue')))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'skillqueue' ? 'active' : null }}" href="{{ route('skillqueue') }}">Skill Queue</a>
                </li>
            @endif

            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterWallet')))
                <li class="nav-item ml-2">
                    <a class="nav-link {{ $currentRouteName === 'wallet.transactions' || $currentRouteName === 'wallet.journal' ? 'active' : null }}" href="{{ route('wallet.transactions') }}">Wallet</a>
                </li>
            @endif
        </ul>
        <hr />
    </div>
</div>
