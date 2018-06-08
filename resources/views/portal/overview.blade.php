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
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-lg-12">
                <ul class="nav nav-pills mb-3  justify-content-center" id="pills-tab" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active" id="bioBodyTab" data-toggle="pill" href="#bioBody" role="tab" aria-controls="pills-home" aria-selected="true">Biography</a>
                  </li>
                  <li class="nav-item ml-2">
                    <a class="nav-link" id="corpHistoryTab" data-toggle="pill" href="#corpHistoryBody2" role="tab" aria-controls="pills-profile" aria-selected="false">Corporation History</a>
                  </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                  <div class="tab-pane fade show active text-center" id="bioBody" role="tabpanel">
                       <div class="col-lg-6 offset-lg-3">
                            {!! $member->info->bio ?: "This character does not have a bio set" !!}
                       </div>
                  </div>
                  <div class="tab-pane fade" id="corpHistoryBody" role="tabpanel">
                      <div class="col-lg-6 offset-lg-3">
                          @if ($member->info->corporationHistory->isNotEmpty())
                              <ul class="list-group">
                                  <?php $corpHistory = $member->info->corporationHistory->sortByDesc('record_id')->values(); ?>
                                  @foreach($corpHistory as $key=>$corp)
                                      <li class="list-group-item">
                                          <div class="media">
                                              <div class="float-left">
                                                  <img src="{{ config('services.eve.urls.img') }}/Corporation/{{ $corp->corporation_id }}_64.png" />
                                              </div>
                                              <div class="media-body ml-2">
                                                  <h5 class="mt-0">{{ !is_null($corp->corporation) ? $corp->corporation->name : "Unknown Corp ". $corp->corporation_id }} {{ $corp->is_deleted ? "(Closed)" : "" }}</h5>
                                                  <p>
                                                      @if ($corpHistory->has($key - 1))
                                                          Left {{ age($corp->start_date, $corpHistory->get($key - 1)->start_date) }} later on {{ $corpHistory->get($key - 1)->start_date->toDateString() }}<br />
                                                      @else
                                                          Been in for {{ age($corp->start_date, now()) }} <br />
                                                      @endif
                                                      Started on {{ $corp->start_date->toDateString() }}
                                                  </p>
                                              </div>
                                          </div>
                                      </li>
                                  @endforeach
                              </ul>
                          @endif
                      </div>
                  </div>
                  <div class="tab-pane fade" id="corpHistoryBody2" role="tabpanel">
                    <div class="row">
                    @if ($member->info->corporationHistory->isNotEmpty())
                    <?php $corpHistory = $member->info->corporationHistory->sortByDesc('record_id')->values(); ?>
                         @foreach($corpHistory as $key=>$corp)
                         <div class="list-group-item col-lg-4">
                              <div class="media">
                                  <div class="float-left">
                                      <img src="{{ config('services.eve.urls.img') }}/Corporation/{{ $corp->corporation_id }}_64.png" />
                                  </div>
                                  <div class="media-body ml-2">
                                      <h5 class="mt-0">{{ !is_null($corp->corporation) ? $corp->corporation->name : "Unknown Corp ". $corp->corporation_id }} {{ $corp->is_deleted ? "(Closed)" : "" }}</h5>
                                      <p>
                                          @if ($corpHistory->has($key - 1))
                                              Left {{ age($corp->start_date, $corpHistory->get($key - 1)->start_date) }} later on {{ $corpHistory->get($key - 1)->start_date->toDateString() }}<br />
                                          @else
                                              Been in for {{ age($corp->start_date, now()) }} <br />
                                          @endif
                                          Started on {{ $corp->start_date->toDateString() }}
                                      </p>
                                  </div>
                              </div>
                          </div>
                          @endforeach
                      @endif
                      </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
@endsection
