@extends('layout.index')

@section('title', 'Welcome To ESIKnife')

@section('content')
    <div class="container">
        <div class="row mt-3">
            <div class="col-lg-3">
                <img src="{{ config('services.eve.urls.img') }}/Character/{{ Auth::user()->id }}_512.jpg" class="img-fluid rounded mx-auto d-block" />
            </div>
            <div class="col-lg-9">
                <h1 class="text-center">Welcome to ESI Knife {{ Auth::user()->info->name }}</h1>
                <hr />
                <p>
                    You are here because you're either interested in viewing your data out-of-game in a secure environment or a recruiter has instructed you come to our site and register as part of their recruitment process so that they can make sure that you are not a spy!. No worries, we'll help you get setup. It is really simple. Below are the scopes that are currently supported by our site, meaning these are the piece of data that we can currently pull from ESI. More are added every day. Please check the scopes for the data that you are interested in viewing and then click the submit button below them.
                </p>
                <h3 class="mb-1">Select the Scopes!</h3>
                <hr />
                @include('extra.alert')
                <form action="{{ route('welcome') }}" method="post">
                    <div class="row">
                        <div class="col-md-6 offset-lg-3">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <div class="float-right">
                                        <a href="#" id="all">[Select All]</a>
                                    </div>
                                    <strong>Character Information</strong>
                                </li>
                                @foreach (collect(config('services.eve.scopes'))->recursive() as $scope)
                                    <label for="{{ $scope->get('key') }}" class="mb-0">
                                        <li class="list-group-item py-auto">
                                            <input type="checkbox" id="{{ $scope->get('key') }}" name="scopes[{{ $scope->get('key') }}]" class="item" /> <span class="ml-2">{{ $scope->get('display') }}</span>
                                        </li>
                                    </label>
                                @endforeach
                                <label for="storeRefreshToken" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="storeRefreshToken" name="storeRefreshToken" class="item" /> <span class="ml-2">Store My Refresh Token</span>
                                    </li>
                                </label>
                            </ul>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="text-center">
                                {{ csrf_field() }}
                                <button type="submit" class="btn btn-primary btn-lg">Authorize Selected Scopes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $('#all').on('click', function(){
            var checkboxes = $(':checkbox.item');
            checkboxes.prop('checked', !checkboxes.prop('checked'));
        });
    </script>
@endsection
