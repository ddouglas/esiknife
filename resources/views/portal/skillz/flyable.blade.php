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
                    <h3>
                        {{ $value->get('name') }} ({{ $value->get('ships')->get('can')->count() }} / {{ $value->get('ships')->get('can')->count() + $value->get('ships')->get('cant')->count() }})
                        <hr />
                    </h3>
                    <br />
                    <div class="row">
                        <div class="col-lg-12">
                            <h5 class="text-center">Can Fly<hr /></h5>
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
                                            Unfortunately, this character cannot fly any of the ships in the {{ $value->get('name') }} ship
                                        </p>

                                    </div>
                                @endforelse
                                <hr />
                            </div>

                        </div>

                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <h5 class="text-center">Cant Fly<hr /></h5>
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
                                            This is epic. This character can fly every ship in the {{ $value->get('name') }} ship group
                                        </p>

                                    </div>
                                @endforelse
                            </div>
                            <hr />

                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
