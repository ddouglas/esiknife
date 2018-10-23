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
                    <a href="{{ route('fittings.load') }}" class="btn btn-danger">
                        Download More Fits
                    </a>
                    <hr />
                    @if (isset($groups) && $groups->isNotEmpty())
                        <div class="list-group-item">
                            Click on a ship group below to filter your fittings to only that group
                        </div>
                        <a href="{{ route('fittings.list') }}" class="list-group-item text-center">All</a>
                        @foreach ($groups as $group)
                            <a href="{{ route('fittings.list', ['group' => $group->id]) }}" class="list-group-item">{{ $group->name }}</a>
                        @endforeach
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
                    @forelse ($fittings as $fitting)
                        <div class="list-group-item">
                            <div class="float-right">
                                <form action="{{ route('fittings.list', ['id' => $fitting->id]) }}" method="post">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <a href="{{ route('fitting.view', ['id' => $fitting->id]) }}" class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>

                            </div>
                            <div class="media">
                                <div class="media mt-0">
                                    <img src="{{ config('services.eve.urls.img') }}/Type/{{ $fitting->type_id }}_64.png" class="rounded img-fluid mr-3" />
                                    <div class="media-body align-center">
                                        <h4>{{ $fitting->name }}</h4>
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
