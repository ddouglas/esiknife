@extends('layout.index')

@section('title', Auth::user()->info->name . " Dashboard")

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center">Welcome to your ESIKnife Dashboard</h1>
                <hr />
            </div>
        </div>
        <div class="row">
            <p>
                Below is a list of character that you are authorized to access. Including your own character. To view the character, click the eye.
            </p>
        </div>
        <div class="row">
            <div class="col-8">
                <h3>Your Character</h3>
                <hr />
                <ul class="list-group">
                    <li class="list-group-item">
                        <div class="float-right">
                            <a href="{{ route('overview', ['id' => Auth::user()->id]) }}" class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>

                        </div>
                        <div class="media mt-0">
                            <img src="{{ config('services.eve.urls.img') }}/Character/{{ Auth::user()->id }}_64.jpg" class="rounded img-fluid mr-3" />
                            <div class="media-body align-center">
                                <h4>{{ Auth::user()->info->name }}</h4>
                                <p>
                                    {{ Auth::user()->info->corporation->name }} / @if(!is_null(Auth::user()->info->alliance)) {{ Auth::user()->info->alliance->name }} @endif
                                </p>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        @if (Auth::user()->accessee->isNotEmpty())
            <div class="row">
                <div class="col-8">
                    <hr />
                    <h3>Character You Are Authorized to Access</h3>
                    <hr />
                    <ul class="list-group">
                        @foreach (Auth::user()->accessee as $accessee)
                            <li class="list-group-item">
                                <div class="float-right">
                                    <a href="{{ route('overview', ['member' => $accessee->id]) }}" class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                </div>
                                <div class="media mt-0">
                                    <img src="{{ config('services.eve.urls.img') }}/Character/{{ $accessee->id }}_64.jpg" class="rounded img-fluid mr-3" />
                                    <div class="media-body align-center">
                                        <h4>{{ $accessee->info->name }}</h4>
                                        <p>
                                            {{ $accessee->info->corporation->name }} / @if(!is_null($accessee->info->alliance)) {{ $accessee->info->alliance->name }} @endif
                                        </p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

    </div>
@endsection
