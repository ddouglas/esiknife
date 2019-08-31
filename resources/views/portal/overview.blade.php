@extends('layout.index')

@section('title', $member->info->name . "'s Overview")

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center">Welcome to your ESIKnife Dashboard</h1>
                <hr />
            </div>
        </div>
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-3">
                <img src="{{ config('services.eve.urls.img') }}/Character/{{ $member->id }}_512.jpg" class="img-fluid rounded mx-auto d-block" />
            </div>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-6">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Gender:</strong> {{ title_case($member->info->gender) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Race:</strong> {{ title_case($member->info->race->name) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Ancestry:</strong> {{ title_case($member->info->ancestry->name) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Bloodline:</strong> {{ title_case($member->info->bloodline->name) }}</li>
                            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterShip')))
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Current Ship:</strong>
                                    {{ $member->ship->name }} ({{ !is_null($member->ship->type) ? $member->ship->type->name : $member->ship->type_id }})
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Birthday:</strong> {{ $member->info->birthday->toDateString() }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Age:</strong> {{ age($member->info->birthday, now()) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Corporation:</strong> {{ $member->info->corporation->name }}</li>
                            @if ($member->info->alliance_id !== null)
                                <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Alliance:</strong> {{ $member->info->alliance->name }}</li>
                            @endif
                            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterLocation')))
                                @if ($scopes->contains(config('services.eve.scopes.readUniverseStructures')))
                                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Current Location:</strong> {{ !is_null($member->location->info) ? $member->location->info->name : "Unknown Location ". $member->location->location_id }}</li>
                                @else
                                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Current Location:</strong> {{ $member->location->system->name }}</li>
                                @endif
                            @endif
                            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterWallet')))
                                <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Wallet Balance:</strong> {{ number_format($member->wallet_balance, 2) }} ISK</li> 
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-lg-12">
                <h4 class="text-center">Corporation History</h4>
                <hr />
                @if ($member->info->history->isNotEmpty())
                    <?php $corpHistory = $member->info->history->sortByDesc('record_id')->values(); ?>
                    @foreach($corpHistory as $key=>$corp)
                        <div class="row">
                            <div class="col-lg-6 offset-lg-3">
                                <div class="list-group-item">
                                    <div class="media">
                                        <div class="float-left">
                                            <img src="{{ config('services.eve.urls.img') }}/Corporation/{{ $corp->corporation_id }}_64.png" />
                                        </div>
                                        <div class="media-body ml-3">
                                            <h5 class="mt-0">{{ !is_null($corp->info) ? $corp->info->name : "Unknown Corp ". $corp->corporation_id }} {{ $corp->is_deleted ? "(Closed)" : "" }}</h5>
                                            <p>
                                                @if ($corpHistory->has($key - 1))
                                                    Left {{ age($corp->start_date, $corpHistory->get($key - 1)->start_date) }} later on {{ $corpHistory->get($key - 1)->start_date->format("m/d/Y") }}<br />
                                                @else
                                                    Been in for {{ age($corp->start_date, now()) }} <br />
                                                @endif
                                                Started on {{ $corp->start_date->format("m/d/Y") }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-center align-bottom">
                                        <a href="{{ config('services.eve.urls.km') }}corporation/{{ $corp->corporation_id }}/" class="btn btn-primary" target="_blank">zKillboard</a>
                                        <a href="{{ config('services.eve.urls.who') }}corp/{{ $corp->info->name }}/" class="btn btn-primary" target="_blank">Eve Who</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
