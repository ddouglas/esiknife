<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item">
                <a class="nav-link {{ $currentRouteName === 'dashboard' ? 'active' : null }}" href="{{ route('dashboard') }}">Overview</a>
            </li>
            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')))
                <li class="nav-item">
                    <a class="nav-link {{ in_array($currentRouteName, ['skillz', 'skillz.flyable']) ? 'active' : null }}" href="{{ route('skillz') }}">Skills</a>
                </li>
            @endif
            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkillQueue')))
                <li class="nav-item">
                    <a class="nav-link {{ $currentRouteName === 'skillqueue' ? 'active' : null }}" href="{{ route('skillqueue') }}">Skill Queue</a>
                </li>
            @endif

            <li class="nav-item">
                <a class="nav-link" href="#">Link</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#">Disabled</a>
            </li>
        </ul>
        <hr />
    </div>
</div>
