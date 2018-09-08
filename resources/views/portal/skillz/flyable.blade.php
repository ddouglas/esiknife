@extends('layout.index')

@section('title', 'Flyable Ships')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        @include('portal.skillz.extra.nav')
        <div class="row">
            <div class="col-lg-12">
                @foreach ($flyable as $value)
                    <h3 class="text-center">
                        <div class="float-right">
                            <a href="#" data-toggle="collapse" data-target="#{{ $value->get('key') }}_body">
                                <i class="fas fa-bars"></i>
                            </a>
                        </div>
                        {{ $value->get('name') }} ({{ $value->get('ships')->get('can')->count() }} / {{ $value->get('ships')->get('can')->count() + $value->get('ships')->get('cant')->count() }})
                        <hr class="white" />
                    </h3>
                    <div class="collapse" id="{{ $value->get('key') }}_body">
                        <div class="row">
                            <div class="col-lg-6">
                                <h5 class="text-center">Can Fly ({{ $value->get('ships')->get('can')->count() }}) <hr class="white" /></h5>
                                <div class="list-group mb-3">
                                    @forelse ($value->get('ships')->get('can') as $can)
                                        <div class="col-lg-12">
                                            <div class="list-group-item">
                                                <div class="media mt-0">
                                                    <img src="{{ config('services.eve.urls.img') }}/Render/{{ $can->id }}_32.png" class="rounded img-fluid mr-3" />
                                                    <div class="media-body align-center">
                                                        <h4>{{ $can->name }}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-lg-12">
                                            <p class="text-white text-center">
                                                Unfortunately, {{ $member->info->name }} cannot fly any of the ships in the {{ $value->get('name') }} ship group
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <h5 class="text-center">Can't Fly ({{ $value->get('ships')->get('cant')->count() }})<hr class="white" /></h5>
                                <div class="list-group mb-3">
                                    @forelse ($value->get('ships')->get('cant') as $cant)
                                        <div class="col-lg-12">
                                            <div class="list-group-item">
                                                <div class="media mt-0">
                                                    <img src="{{ config('services.eve.urls.img') }}/Render/{{ $cant->id }}_32.png" class="rounded img-fluid mr-3" />
                                                    <div class="media-body align-center">
                                                        <h4>{{ $cant->name }}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-lg-12">
                                            <p class="text-white text-center">
                                                {{ $member->info->name }} can fly all of the ship in the {{ $value->get('name') }} ship group.
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
