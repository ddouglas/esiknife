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
                        <div class="float-right  mb-2">
                            <button type="button" class="btn btn-primary" data-toggle="collapse" data-target="#{{ $value->get('key') }}_collapse">Expand</button>
                        </div>
                        {{ $value->get('name') }} ({{ $value->get('ships')->get('can')->count() }} / {{ $value->get('ships')->get('can')->count() + $value->get('ships')->get('cant')->count() }})
                    </h3>
                    <br />
                    <div class="collapse" id="{{ $value->get('key') }}_collapse">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-center">Can Fly</h5>
                                <div class="row justify-content-center mb-3">
                                    @forelse ($value->get('ships')->get('can') as $can)
                                        <div class="col-lg-2">
                                            <div class="card">
                                                <img src="{{ config('services.eve.urls.img') }}/Render/{{ $can->id }}_128.png" class="card-img-top" title="{{ $can->name }}" />
                                                <div class="card-body">
                                                    <p class="card-text">
                                                        {{ $can->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-lg-12">
                                            <p class="text-white">
                                                Unfortunately, this character cannot fly any of the ships in this group
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="text-center">Cant Fly</h5>
                                <div class="row">
                                    @forelse ($value->get('ships')->get('cant') as $cant)
                                        <div class="col-lg-2">
                                            <div class="card">
                                                <img src="{{ config('services.eve.urls.img') }}/Render/{{ $cant->id }}_128.png" class="card-img-top" title="{{ $cant->name }}" />
                                                <div class="card-body">
                                                    <p class="card-text">
                                                        {{ $cant->name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-lg-12">
                                            <p class="text-white">
                                                This is epic. This character can fly every ship in this group
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                                {{-- @foreach ($value->get('ships')->get('cant') as $cant)
                                    <img src="{{ config('services.eve.urls.img') }}/Render/{{ $cant->id }}_64.png" class="img-fluid rounded" title="{{ $cant->name }}" />
                                @endforeach --}}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
