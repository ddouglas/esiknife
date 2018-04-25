@extends('layout.index')

@section('title', Auth::user()->info->name . "'s ESIKnife Dashboard")

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
                <img src="{{ config('services.eve.urls.img') }}/Character/{{ Auth::user()->id }}_512.jpg" class="img-fluid rounded mx-auto d-block" />
            </div>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-6">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Gender:</strong> {{ title_case(Auth::user()->info->gender) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Race:</strong> {{ title_case(Auth::user()->info->race->name) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Ancestry:</strong> {{ title_case(Auth::user()->info->ancestry->name) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Bloodline:</strong> {{ title_case(Auth::user()->info->bloodline->name) }}</li>
                            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterShip')))
                                <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Current Ship:</strong> {{ Auth::user()->ship->name }} ({{ Auth::user()->ship->type->name }})</li>
                            @endif
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Birthday:</strong> {{ Auth::user()->info->birthday->toDateString() }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Age:</strong> {{ age(Auth::user()->info->birthday, now()) }}</li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Corporation:</strong> {{ Auth::user()->info->corporation->name }}</li>
                            @if (Auth::user()->info->alliance_id !== null)
                                <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Alliance:</strong> {{ Auth::user()->info->alliance->name }}</li>
                            @endif
                            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterLocation')))
                                @if ($scopes->contains(config('services.eve.scopes.readUniverseStructures')))
                                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Current Location:</strong> {{ Auth::user()->location->info->name }}</li>
                                @else
                                    <li class="list-group-item d-flex justify-content-between align-items-center"><strong>Current Location:</strong> {{ Auth::user()->location->system->name }}</li>
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
                    <a class="nav-link" id="corpHistoryTab" data-toggle="pill" href="#corpHistoryBody" role="tab" aria-controls="pills-profile" aria-selected="false">Corporation History</a>
                  </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                  <div class="tab-pane fade show active text-center" id="bioBody" role="tabpanel">
                       <div class="col-lg-6 offset-lg-3">
                            {!! Auth::user()->info->bio ?: "This character does not have a bio set" !!}
                       </div>
                  </div>
                  <div class="tab-pane fade" id="corpHistoryBody" role="tabpanel">
                      <div class="col-lg-6 offset-lg-3">
                          @if (Auth::user()->info->corporationHistory->isNotEmpty())
                              <ul class="list-group">
                                  <?php $corpHistory = Auth::user()->info->corporationHistory->sortByDesc('record_id')->values(); ?>
                                  @foreach($corpHistory as $key=>$corp)
                                      <li class="list-group-item">
                                          <div class="media">
                                              <div class="float-left">
                                                  <img src="{{ config('services.eve.urls.img') }}/Corporation/{{ $corp->corporation->id }}_64.png" />
                                              </div>
                                              <div class="media-body ml-2">
                                                  <h5 class="mt-0">{{ $corp->corporation->name }} {{ $corp->is_deleted ? "(Closed)" : "" }}</h5>
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
                </div>
            </div>
        </div>
    </div>
@endsection
