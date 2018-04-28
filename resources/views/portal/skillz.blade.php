@extends('layout.index')

@section('title', 'My Skills')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center">Your Skillz</h1>
                <hr />
            </div>
        </div>
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-3">
                @include('portal.extra.skillz_nav')
            </div>
            <div class="col-lg-9">
                <table class="table table-bordered">
                    @foreach ($skillz as $value)
                        <tr>
                            <th colspan="4" class="text-center">
                                {{ $value->get('name') }}
                            </td>
                        </tr>
                        @if ($loop->first)
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
                        @endif
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
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection
