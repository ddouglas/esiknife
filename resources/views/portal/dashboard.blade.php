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
                Below is a list of characters that you are authorized to access, including your own character. To view the character, click the eye.
            </p>
        </div>
        <div class="row">
            <div class="col-md-8">
                <h3>Your Character</h3>
                <hr />
                @if (Session::has('to'))
                    @if (starts_with(Session::get('to'), url('/settings/grant/')))
                        <div class="alert alert-info">
                            <h5>You have a pending grant URL</h5>
                            <p>
                                You have grant url pending. To finish the process of granting another character access to your data, click <a href="{{ Session::get('to') }}">here</a>.<br />If you no longer want to grant access to your data, click <a href="{{ route('dashboard', ['action' => 'delete_pending_grant']) }}">here</a>
                            </p>
                        </div>
                    @endif
                @endif
                @include('extra.alert')
                <ul class="list-group">
                    @foreach(Auth::user()->alts as $alt)
                        <li class="list-group-item">
                            <div class="float-right">
                                <a href="{{ route('welcome') }}" class="btn btn-primary">
                                    <i class="fas fa-sync"></i>
                                </a>
                                <a href="{{ route('overview', ['id' => $alt->id]) }}" class="btn btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            <div class="media mt-0">
                                <img src="{{ config('services.eve.urls.img') }}/Character/{{ $alt->id }}_64.jpg" class="rounded img-fluid mr-3" />
                                <div class="media-body align-center">
                                    <h4>{{ $alt->info->name }}</h4>
                                    <p>
                                        {{ $alt->info->corporation->name }} / @if(!is_null($alt->info->alliance)) {{ $alt->info->alliance->name }} @endif
                                    </p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="row">
                    <div class="col-12 mt-3">
                        <div class="btn-group float-right">
                            <a href="{{ route('welcome') }}" class="btn btn-secondary ">Add Character</a>
                            <a href="{{ route('settings.access') }}" class="btn btn-info">Manage Access</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
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
                                            {{ !is_null( $accessee->info->corporation) ? $accessee->info->corporation->name : "Unknown Corporation ".  $accessee->info->corporation_id }} @if(!is_null($accessee->info->alliance)) {{ "/ ".$accessee->info->alliance->name }} @endif
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
