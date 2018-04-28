<h4 class="text-center">Contract Menu</h4>
<div class="list-group">
    @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')))
        <a href="{{ route('contracts') }}" class="list-group-item list-group-item-action {{ $currentRouteName === 'contracts' ? 'active' : null }}">My Contracts</a>
        <a href="{{ route('contracts.interactions') }}" class="list-group-item list-group-item-action {{ $currentRouteName === 'contracts.interactions' ? 'active' : null }}">Interactions</a>
    @endif
</div>
