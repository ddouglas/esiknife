@extends('layout.index')

@section('title', 'Default Layout')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center">Your Skillz</h1>
                <hr />
            </div>
        </div>
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-3">
                <h4 class="text-center">Skill Menu</h4>
                <div class="list-group">
                    @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkills')))
                        <a href="{{ route('skillz') }}" class="list-group-item active">My Skillz</a>
                    @endif
                    @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterSkillQueue')))
                        <a href="{{ route('skillz.flyable') }}" class="list-group-item">Can Fly</a>
                    @endif
                </div
            </div>
        </div>
    </div>
@endsection
