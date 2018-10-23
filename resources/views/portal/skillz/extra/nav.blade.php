<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-pills justify-content-center">
            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')['scope']))
                <li class="nav-item ml-2">
                    <a class="nav-link  {{ $currentRouteName === 'skillz' ? 'active' : null }}" href="{{ route('skillz', ['member' => $member]) }}">Skill List</a>
                </li>
                <li class="nav-item ml-2">
                    <a class="nav-link  {{ $currentRouteName === 'skillz.flyable' ? 'active' : null }}" href="{{ route('skillz.flyable', ['member' => $member]) }}">Flyable Ships</a>
                </li>
                <li class="nav-item ml-2">
                    <a class="nav-link  {{ $currentRouteName === 'skillz.analyzer' ? 'active' : null }}" href="{{ route('skillz.analyzer', ['member' => $member]) }}">Fitting Analyzer</a>
                </li>
            @endif
        </ul>
        <hr />
    </div>
</div>
