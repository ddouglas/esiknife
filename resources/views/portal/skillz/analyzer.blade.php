@extends('layout.index')

@section('title', 'My Bookmarks')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="text-center">My Fittings</h1>
                <hr />
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="list-group">
                    <div class="list-group-item">
                        Click on a ship group below to filter your fittings to only that group
                    </div>
                    <a href="{{ route('skillz.analyzer', ['member' => $member->id]) }}" class="list-group-item text-center">All</a>
                    @foreach ($groups as $group)
                        <a href="{{ route('skillz.analyzer', ['member' => $member->id, 'group' => $group->id]) }}" class="list-group-item">{{ $group->name }}</a>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-8">
                @include('extra.alert')
                <div class="list-group">
                    @forelse ($fittings as $fitting)
                        <div class="list-group-item">
                            <div class="media">
                                <div class="media mt-0">
                                    @if ($fitting->missing->isNotEmpty())
                                        <div class="mr-4 align-self-center">
                                            <i class="fas fa-times fa-3x "></i>
                                        </div>
                                    @else
                                        <div class="mr-3 align-self-center">
                                            <i class="fas fa-check fa-3x"></i>
                                        </div>
                                    @endif
                                    <img src="{{ config('services.eve.urls.img') }}/Type/{{ $fitting->type_id }}_64.png" class="rounded img-fluid mr-3" />
                                    <div class="media-body align-center">
                                        <h4>{{ $fitting->name }}</h4>
                                        @if ($fitting->missing->isNotEmpty())
                                            Missing Skillz: @foreach ($fitting->missing as $skill){{ $skill->get('name')  }} {{ num2rom($skill->get('level')) }}@if (!$loop->last){{ ", " }}@endif @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        @if(Auth::user()->scopes->contains(config('services.eve.scopes.readCharacterFittings')['scope']))
                            <a href="{{ route('fittings.load') }}" class="list-group-item">
                                You do not currently have any fits loaded. Click here to download some fits from ESI.
                            </a>
                        @else
                            <a href="{{ route('settings.token') }}" class="list-group-item">
                                Your token does not support reading fits from ESI. Please click here to update your token to allow the reading of fits from ESI.
                            </a>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
