@extends('layout.index')

@section('title', 'My Skillqueue')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header text-center">
                        # Skills Queued
                    </div>
                    <div class="card-body mx-auto">
                        <h4>{{ Auth::user()->skillQueue()->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header text-center">
                        Total # of SP in Training
                    </div>
                    <div class="card-body mx-auto">
                        <h4>{{ number_format($spTraining->sum()) }} <small>sp</small></h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header text-center">
                        Anticipated Queue Completion Date
                    </div>
                    <div class="card-body mx-auto">
                        <h4>{{ $queueComplete }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <hr />
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header text-center">
                        Group In Training
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            @foreach ($groupsTraining as $group)
                                <tr>
                                    <td>
                                        {{ $group->name }}
                                    </td>
                                    <td>
                                        {{ $group->training }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        Skill Queue
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            @foreach (Auth::user()->skillQueue as $item)
                                <tr>
                                    <td class="text-white">
                                        {{ $item->pivot->queue_position }}
                                    </td>
                                    <td>
                                        {{ $item->group->name }}
                                    </td>
                                    <td>
                                        {{ $item->name }} {{ num2rom($item->pivot->finished_level) }} (Training {{ number_format($item->pivot->level_end_sp - $item->pivot->training_start_sp) }} sp) <strong>|</strong> Training Complete on: <strong>{{ \Carbon\Carbon::parse($item->pivot->finish_date)->toDayDateTimeString() }}</strong><hr /> {{ $item->description }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
