@extends('layout.index')

@section('title', $member->info->name ."'s Clones")

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">

                @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterClones')))
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header text-center">
                                Current Death Clone Location
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-bordered m-0">
                                    <tr>
                                        <th>
                                            {{ ucfirst($member->clone_location_type) }}
                                        </th>
                                        <th>
                                            {{ $member->clone->name }}
                                        </th>
                                    </tr>
                                </table>
                            </div>
                        </div>

                    </div>
                @endif
                @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterImplants')))
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header text-center">
                                Current Clone Implants
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
                                    @foreach($member->implants as $implant)
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
                        </div>
                    </div>

                @endif
                @if (isset($scopes) && $scopes->contains(config('services.eve.scopes.readCharacterClones')))
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header text-center">
                                Current Jump Clone Locations
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-bordered m-0">
                                    @foreach ($member->jumpClones as $clone)
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

                    </div>
                @endif


        </div>
    </div>
@endsection
