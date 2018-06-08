@extends('layout.index')

@section('title', 'Sharing My Data')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        Sharing Your Data
                    </div>
                    <div class="card-body">
                        The entire purpose of ESIKnife is so that you can easily manage your data and share it with your peers or a recruiter. Share it with whomever you want, for whatever reason.
                    </div>
                </div>
                <hr />
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                @include('extra.alert')
                <div class="card">
                    <div class="card-header text-center">
                        Characters Who Can See My Data
                    </div>
                    <div class="card-body p-0">
                        <form action="{{ route('settings.access', ['scope' => "accessor"]) }}" method="post">
                            <table class="table table-bordered m-0">
                                @forelse (Auth::user()->accessor as $accessor)
                                    <tr>
                                        <td>
                                            <div class="float-right">
                                                <button class="btn btn-primary" type="submit" name="action" value="modify">
                                                    <i class="fas fa-step-forward"></i>
                                                </button>
                                                <button class="btn btn-danger" name="remove" value="{{ $accessor->id }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                            <div class="media mt-0">
                                                <img src="{{ config('services.eve.urls.img') }}/Character/{{ $accessor->id }}_64.jpg" class="rounded img-fluid mr-3" />
                                                <div class="media-body">
                                                    {{ $accessor->info->name }}

                                                    <hr class="bg-white mr-3" />
                                                    <p>
                                                        This accessor can view data related to the following scopes. Uncheck to remove access:
                                                    </p>
                                                    <?php $accessorScopes = collect(json_decode($accessor->pivot->access, true)); ?>
                                                    <ul class="list-unstyled">
                                                        @foreach(Auth::user()->scopes as $key => $appScope)
                                                            <li>
                                                                <label for="access[{{ $accessor->id }}][{{ $key }}]">
                                                                    <input type="checkbox" name="access[{{ $accessor->id }}][{{ $appScope }}]" id="access[{{ $accessor->id }}][{{ $key }}]" @if ($accessorScopes->containsStrict($appScope)){{ "checked" }}@endif /> {{ $appScope }}
                                                                </label>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    {{-- @foreach (json_decode($accessor->pivot->access, true) as $key => $scope)
                                                    <label for="access[{{ $accessor->id }}][{{ $key }}]">
                                                        <input type="checkbox" name="access[{{ $accessor->id }}][{{ $scope }}]" id="access[{{ $accessor->id }}][{{ $key }}]" checked /> {{ $scope }}
                                                    </label>
                                                    @endforeach --}}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>
                                            Nobody has access to your ESIKnife Data Right Now
                                        </td>
                                    </tr>
                                @endforelse
                                <tr>
                                    <td>
                                        <div class="input-group mb-0">
                                            <input type="text" name="char_name" value="{{ old('char_name') }}" class="form-control select2-module" placeholder="Grant Access To Any Char">
                                            <div class="input-group-append">
                                                @csrf
                                                <button class="btn btn-outline-secondary" name="action" value="search" type="submit">Add</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @if(isset($results) && $results->count() > 0)
                                    @foreach($results as $result)
                                        <tr>
                                            <td>
                                                <div class="float-right">
                                                    <button type="submit" name="select" value="{{ $result->get('id') }}" class="btn btn-primary">Select</button>
                                                </div>
                                                <div class="media mt-0">
                                                    <img src="{{ config('services.eve.urls.img') }}/Character/{{ $result->get('id') }}_64.jpg" class="rounded img-fluid mr-3" />
                                                    <div class="media-body align-center">
                                                        {{ $result->get('name') }}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td class="text-center">
                                            <a href="{{ route('settings.access') }}" class="btn btn-danger">Clear Results</a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header text-center">
                        Characters Whose Data I Can See
                    </div>
                    <div class="card-body p-0">
                        <form action="{{ route('settings.access', ['scope' => "accessee"]) }}" method="post">
                            <table class="table table-bordered m-0">
                                @forelse (Auth::user()->accessee as $accessee)
                                    <tr>
                                        <td>
                                            <div class="float-right">
                                                <button class="btn btn-danger" name="remove" value="{{ $accessee->id }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                            <div class="media mt-0">
                                                <img src="{{ config('services.eve.urls.img') }}/Character/{{ $accessee->id }}_64.jpg" class="rounded img-fluid mr-3" />
                                                <div class="media-body">
                                                    {{ $accessee->info->name }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>
                                            You do not have access to anybodies data right now
                                        </td>
                                    </tr>
                                @endforelse
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
