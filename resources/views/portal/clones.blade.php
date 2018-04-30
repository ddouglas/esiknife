@extends('layout.index')

@section('title', 'My Clones')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-4 offset-lg-2 col-md-6">
                @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterClones')))
                    <div class="card">
                        <div class="card-header text-center">
                            Current Death Clone Location
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered m-0">
                            <tr>
                                <th>
                                    {{ ucfirst(Auth::user()->clone_location_type) }}
                                </th>
                                <th>
                                    {{ Auth::user()->clone->name }}
                                </th>
                            </tr>
                        </table>
                    </div>
                @endif
                @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterImplants')))
                    <div class="card">
                        <div class="card-header text-center">
                            Current Clone Implants
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered m-0">
                            <tr>
                                <td>
                                    Slot
                                </td>
                                <td>
                                    Name
                                </td>
                            </tr>
                            @foreach(Auth::user()->implants as $implant)
                                <tr>
                                    <td>
                                        {{ $implant->attributes->where('attribute_id', 331)->first()->value }}
                                    </td>
                                    <td>
                                        {{ $implant->name }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            </div>
            @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterClones')))
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-header text-center">
                            Current Jump Clone Locations
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered m-0">
                            @foreach (Auth::user()->jumpClones as $clone)
                                <tr>
                                    <th>
                                        {{ ucfirst($clone->location_type) }}
                                    </th>
                                    <th>
                                        {{$clone->location->name }}
                                    </th>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
