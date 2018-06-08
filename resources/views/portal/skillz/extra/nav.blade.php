<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-pills justify-content-center">
            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')))
                <li class="nav-item ml-2">
                    <a class="nav-link  {{ $currentRouteName === 'skillz' ? 'active' : null }}" href="{{ route('skillz', ['member' => $member]) }}">My Skillz</a>
                </li>
                <li class="nav-item ml-2">
                    <a class="nav-link  {{ $currentRouteName === 'skillz.flyable' ? 'active' : null }}" href="{{ route('skillz.flyable', ['member' => $member]) }}">What I Can Fly</a>
                </li>
            @endif
        </ul>
        <hr />
    </div>
</div>
