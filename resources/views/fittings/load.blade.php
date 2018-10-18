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
                        <a href="{{ route('fittings.load') }}" class="list-group-item list-group-item">All</a>
                        @foreach ($groups as $group)
                            <a href="{{ route('fittings.load', ['group' => $group->id]) }}" class="list-group-item">{{ $group->name }}</a>
                        @endforeach
                    @else
                        <div class="list-group-item">
                            There are no fits to pulls ship groups from. Please download some fits from ESI to populate this menu
                        </div>
                    @endif
                </div>
                <a href="{{ route('fittings.load', ['action' => 'clear']) }}" class="btn btn-block btn-danger mt-2">Clear Fittings Data</a>
            </div>
            <div class="col-lg-8">
                <div class="list-group">
                    @if ($fittings->isNotEmpty())
                        @foreach ($fittings as $fitting)
                            <div class="list-group-item">
                                <div class="float-right">
                                    <div class="btn-group">
                                        <a href="{{ route('fittings.load', ['action' => 'save', 'id' => $fitting->get('id')]) }}" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="media">
                                    <div class="media mt-0">
                                        <img src="{{ config('services.eve.urls.img') }}/Type/{{ $fitting->get('ship_type_id') }}_64.png" class="rounded img-fluid mr-3" />
                                        <div class="media-body align-center">
                                            <h4>{{ $fitting->get('name') }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @if (!Request::get('group'))
                            <form action="{{ route('fittings.load') }}" method="post">
                                <div class="list-group-item">
                                    There currently no fits loaded. Please use the below to loads your fits from easy. <strong>Depending on the number of fits you have, it may take awhile for the page to load.</strong>
                                </div>
                                {{ csrf_field() }}
                                <br />
                                <button type="submit" class="btn btn-primary">Download Fits</button>
                            </form>
                        @else
                            <div class="list-group-item">
                                There are no fits currently in that group. Please try another group. To clear the filter, click the "Clear Filter" button at the top of the menu to the left.
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
