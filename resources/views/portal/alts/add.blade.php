@extends('layout.index')

@section('title', 'Welcome To ESIKnife')

@section('content')
    <div class="container">
        <div class="row mt-3">
            <div class="col-lg-3">
                <img src="{{ config('services.eve.urls.img') }}/Character/1_512.jpg" class="img-fluid rounded mx-auto d-block" />
            </div>
            <div class="col-lg-9">
                <h1 class="text-center">Add A Character To Your Account</h1>
                <hr />
                <p>
                    Welcome to the Add Character scope authorization page. Select the scopes you want to authorize on your the character that you would like to add to your account and then click the authorize button below. You will be redirected to the SSO Authorization Screen to authenticate with the new character.
                </p>
                <h3 class="mb-1">Select the Scopes!</h3>
                <hr />
                @include('extra.alert')
                <form action="{{ route('alt.add') }}" method="post">
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
