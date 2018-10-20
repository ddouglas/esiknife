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
                    @if (isset($groups) && $groups->isNotEmpty())

                    @else
                        <div class="list-group-item">
                            There are no fits to pulls ship groups from. Please download some fits from ESI to populate this menu
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-lg-8">
                @include('extra.alert')
                <div class="list-group">
                    @forelse (Auth::user()->fittings as $fitting)
                        <a href="{{ route('fitting.view', ['id' => $fitting->id]) }}">{{ $fitting->name }}</a>
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
