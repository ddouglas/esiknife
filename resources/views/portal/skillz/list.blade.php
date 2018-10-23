@extends('layout.index')

@section('title', Auth::user()->info->name . "'s Skills")

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        @include('portal.skillz.extra.nav')
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header text-center">
                        Total Number of Skillz
                    </div>
                    <div class="card-body text-center">
                        <h4>{{ $total_count }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header text-center">
                        Total Skillpoints
                    </div>
                    <div class="card-body text-center">
                        <h4>{{ number_format($member->total_sp) }} sp</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <hr class="white" />
                @foreach ($skillz as $value)
                    <h4 class="text-center">
                        <div class="float-right">
                            <a href="#" data-toggle="collapse" data-target="#{{ $value->get('key') }}_body">
                                <i class="fas fa-bars"></i>
                            </a>
                        </div>
                        {{ $value->get('name') }} ({{ number_format($value->get('info')->get('total_sp'), 0) }} sp)
                        <hr class="white" />
                    </h4>
                    <div class="collapse" id="{{ $value->get('key') }}_body">
                        <table class="table">
                            <tr>
                                <th class="text-center">
                                    Skill Name
                                </th>
                                <th class="text-center">
                                    Active Skill Level
                                </th>
                                <th class="text-center">
                                    Trained Skill Level
                                </th>
                                <th class="text-center">
                                    Skill Points Trained
                                </th>
                            </tr>
                            @foreach ($value->get('skills') as $skill)
                                <tr>
                                    <td>
                                        {{ $skill->name }}
                                    </td>
                                    <td class="text-center">
                                        {{ $skill->pivot->active_skill_level }}
                                    </td>
                                    <td class="text-center">
                                        {{ $skill->pivot->trained_skill_level }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($skill->pivot->skillpoints_in_skill, 0) }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
