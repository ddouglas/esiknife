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
            <div class="col-12">
                <p>
                    Below is a list of characters that you are authorized to access, including your own character. To view the character, click the eye.
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <h3>My Character(s)</h3>
                <hr />
                @include('extra.alert')
                <ul class="list-group">
                    @foreach(Auth::user()->alts as $alt)
                        <li class="list-group-item">
                            <div class="float-right">
                                <div class="btn-group">
                                    <a href="{{ route('overview', ['id' => $alt->id]) }}" class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-primary" type="button" data-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="{{ route('dashboard', ['action' => "refresh", 'id' => $alt->id]) }}">
                                                <i class="fas fa-sync mr-2"></i> Refresh Character
                                            </a>
                                            @if ($alt->id != $alt->main)
                                                <a class="dropdown-item" href="{{ route('dashboard', ['action' => "swap_main", 'id' => $alt->id]) }}">
                                                    <i class="fas fa-random mr-2"></i> Swap Main
                                                </a>
                                                <a class="dropdown-item" href="{{ route('alt.remove', ['id' => $alt->id]) }}">
                                                    <i class="fas fa-trash mr-2"></i> Remove Alt
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="media mt-0">
                                <img src="{{ config('services.eve.urls.img') }}/Character/{{ $alt->id }}_64.jpg" class="rounded img-fluid mr-3" />
                                <div class="media-body align-center">
                                    <h4>{{ $alt->info->name }} {{ $alt->id == $alt->main ? "[Main]" : "[Alt]" }}</h4>
                                    <p>
                                        {{ $alt->info->corporation->name }} @if(!is_null($alt->info->alliance)) {{ "/ ". $alt->info->alliance->name }} @endif
                                    </p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="row">
                    <div class="col-12 mt-3">
                        <div class="btn-group float-right">
                            <a href="{{ route('alt.add') }}" class="btn btn-secondary">Add Character</a>
                            <a href="{{ route('settings.access') }}" class="btn btn-info">Manage Access</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                @if (Session::has('to'))
                    @if (starts_with(Session::get('to'), url('/settings/grant/')))
                        <div class="card">
                            <div class="card-header text-center">
                                Pending Grant Notice
                            </div>
                            <div class="card-body">
                                You currently have a pending grant. To complete the grant process, please click the "Go To Grant" button below, other click on the "Delete Grant" button to delete the grant.
                            </div>
                            <div class="card-footer text-center">
                                <a href="{{ Session::get('to') }}" class="btn btn-primary btn-sm">Go To Grant</a>
                                <a href="{{ route('dashboard', ['action' => 'delete_pending_grant']) }}" class="btn btn-danger btn-sm">Delete Grant</a>
                            </div>
                        </div>
                    @endif
                @endif
                <div class="card">
                    <div class="card-header">
                        Job Status
                    </div>
                    <div class="list-group">
                        <li class="list-group-item">
                            <div class="float-right">
                                <span id="countPending">{{ $jobs->get('pending') }}</span>
                            </div>
                            Pending Jobs
                        </li>
                        <li class="list-group-item">
                            <div class="float-right">
                                <span id="countFinished">{{ $jobs->get('finished') }}</span>
                            </div>
                            Completed Jobs
                        </li>
                        <li class="list-group-item">
                            <div class="float-right">
                                <span id="countFailed">{{ $jobs->get('failed') }}</span>
                            </div>
                             Jobs That Failed
                        </li>
                        <li class="list-group-item">
                            <em>This module updates every {{ config('services.eve.updateInterval') }} seconds</em><br>
                        </li>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <hr />
                <h3>Character You Are Authorized to Access</h3>
                <hr />
            </div>
        </div>
        <div class="row">
            <div class="col-8">
                @if (Auth::user()->accessee->isNotEmpty())
                    <form action="{{ route('settings.access', ['scope' => "accessee"]) }}" method="post">
                        <ul class="list-group">
                            @foreach (Auth::user()->accessee as $accessee)
                                <li class="list-group-item">
                                    <div class="float-right">
                                        <a href="{{ route('overview', ['member' => $accessee->id]) }}" class="btn btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        {{ csrf_field() }}
                                        <button type="submit" class="btn btn-danger" name="remove" value="{{ $accessee->id }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                    <div class="media mt-0">
                                        <img src="{{ config('services.eve.urls.img') }}/Character/{{ $accessee->id }}_64.jpg" class="rounded img-fluid mr-3" />
                                        <div class="media-body align-center">
                                            <h4>{{ $accessee->info->name }}</h4>
                                            <p>
                                                {{ !is_null( $accessee->info->corporation) ? $accessee->info->corporation->name : "Unknown Corporation ".  $accessee->info->corporation_id }} @if(!is_null($accessee->info->alliance)) {{ "/ ".$accessee->info->alliance->name }} @endif
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </form>
                @else
                    <p>
                        Currently, you do not have access to any other characters data other than your own
                    </p>
                @endif
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-header text-center">
                        Accessor Menu
                    </div>
                    <div class="list-group">
                        <a href="#" class="list-group-item">
                            Fitting Manager
                        </a>
                        <a href="#" class="list-group-item">
                            Skill List Manager
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        @if ($jobs->get('pending') > 0)
            interval = {{ config('services.eve.updateInterval') * 1000 }};
            function updateJobs() {
                $.ajax({
                    url: "{{ route('api.jobs.status', ['id' => Auth::user()->id]) }}",
                    type: 'GET',
                    dataType: 'json',
                    success: function (data, textStatus, request) {
                        console.log(data)
                        document.getElementById('countPending').innerHTML = data.pending;
                        document.getElementById('countFinished').innerHTML = data.finished;
                        document.getElementById('countFailed').innerHTML = data.failed;
                        if (data.pending == 0) {
                            clearInterval(update);
                        }
                    }
                });
            };

            $(document).ready(function ()  {
                update = setInterval(updateJobs, interval);
            });
        @endif
    </script>
@endsection
