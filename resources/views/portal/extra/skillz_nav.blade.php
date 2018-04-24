<h4 class="text-center">Skill Menu</h4>
<div class="list-group">
    @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')))
        <a href="{{ route('skillz') }}" class="list-group-item list-group-item-action {{ $currentRouteName === 'skillz' ? 'active' : null }}">My Skillz</a>
        <a href="{{ route('skillz.flyable') }}" class="list-group-item list-group-item-action {{ $currentRouteName === 'skillz.flyable' ? 'active' : null }}">Can Fly</a>
    @endif
</div>
