<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('dashboard') }}">Overview</a>
            </li>
            {{-- {{ isset($scopes) && ($scopes->contains(config('services.eve.scopes.readCharacterSkills')) || $scopes->contains('services.eve.scopes.readCharacterSkillQueue')) }} --}}
            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('skillz') }}">Skills</a>
                </li>
            @endif
            @if (isset($scopes) && $scopes->contains('services.eve.scopes.readCharacterSkillQueue')))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('skillqueue') }}">Skill Queue</a>
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
