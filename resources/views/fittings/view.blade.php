@extends('layout.index')

@section('title', $fitting->name)

@section('content')
    <div class="container">
        <div class="row mt-2">
            <div class="col-lg-12">
                <h1 class="text-center">
                    <div class="float-left">
                        <a href="{{ route('fittings.list') }}" class="btn btn-info">
                            <i class="fas fa-step-backward"></i>
                        </a>
                    </div>
                    {{ $fitting->name }}
                </h1>
                <hr />
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="list-group">
                    <div class="list-group-item text-center">
                        <strong>Fitting Breakdown</strong>
                    </div>
                    <div class="list-group-item">
                        <div class="row">
                            <div class="col-md-3">
                                <img src="{{ config('services.eve.urls.img') }}/Render/{{ $fitting->type_id }}_256.png" class="rounded img-fluid" />
                            </div>
                            <div class="col-md-9">
                                <p>
                                    <strong>Hull: </strong> {{ $fitting->hull->group->name }}
                                </p>
                                <p>
                                    <strong>Ship: </strong> {{ $fitting->hull->name }}
                                </p>
                                <p>
                                    <strong>Description: </strong><br />{{ $fitting->description }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @foreach ($layout as $header => $items)
                        <div class="list-group-item text-center">
                            <strong>{{ ucwords(implode(' ', explode('_',$header))) }}</strong>
                        </div>
                        @foreach ($layout[$header] as $item)
                            <div class="list-group-item">
                                <img src="{{ config('services.eve.urls.img') }}/Type/{{ $item['id'] }}_32.png" class="rounded img-fluid mr-3" /> {{ number_format($item['quantity']) }}x {{ $item['name'] }}
                            </div>
                        @endforeach
                    @endforeach
                </div>

            </div>
            <div class="col-lg-6">
                <div class="list-group-item text-center">
                    <strong>Fitting Skill Requirements</strong>
                </div>
                @foreach($fitting->skills->sortByDesc('rank') as $skill)
                    <div class="list-group-item">
                        <div class="float-right">
                            @for($x=0;$x<$skill->get('level');$x++)
                                <i class="fas fa-square"></i>
                            @endfor
                        </div>
                        {{ $skill->get('name') }} {{ num2rom($skill->get('level')) }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
