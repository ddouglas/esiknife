@extends('layout.index')

@section('title', 'My Assets')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-10 offset-lg-1 col-md-12 offset-md-0">
                <div class="accordian" id="assetsCollapse">
                    @foreach ($assets as $location)
                        <div class="card">
                            <div class="card-header" id="assets_{{ $location->get('info')->id }}" data-toggle="collapse" data-target="#contents_{{ $location->get('info')->id }}" >
                                <h5 class="mb-0">
                                    {{ $location->get('info')->name }}
                                </h5>
                            </div>
                            <div id="contents_{{ $location->get('info')->id }}" class="collapse" data-parent="#assetCollapse">
                                <div class="card-body p-0">
                                    <table class="table table-bordered m-0">
                                        @foreach ($location->get('assets') as $item)
                                            <tr>
                                                <td>
                                                    <div class="media">
                                                        <img src="{{ config('services.eve.urls.img') }}/Type/{{ $item->get('type_id') }}_32.png" class="rounded img-fluid" />
                                                        <div class="media-body align-middle ml-3">
                                                            {{ $item->get('type')->get('name') }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    {{ $item->get('quantity') }}
                                                </td>
                                                <td>
                                                    {{ $item->get('type')->get('group')->get('name') }}
                                                </td>
                                                <td>
                                                    {{ $item->get('type')->get('volume') * $item->get('quantity') }} m<sup>3</sup>
                                                </td>
                                                <td>
                                                    N/A ISK
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
