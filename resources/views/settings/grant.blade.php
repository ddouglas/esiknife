@extends('layout.index')

@section('title', 'Sharing My Data')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header">
                        Granting Access to your data
                    </div>
                    <div class="card-body">
                        {{ $isGroup ? $grant->name : $grant->member->info->name }} is requesting access to your data with the following scopes. Please review this entire screen before clicking <strong>"Grant Access to Data"</strong> or <strong>"Do not grant Access to Data"</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group">
                            <div class="list-group-item">
                                <div class="float-right">
                                    Scope is Registered?
                                </div>
                                Requested Scope
                            </div>
                            @foreach ($grant->scopes as $scope)
                                <div class="list-group-item">
                                    <div class="float-right">
                                        {{ Auth::user()->scopes->containsStrict($scope) ? "Scope is Registerd" : "Scope is not Registered" }}
                                    </div>
                                    {{ $scope }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>If a scope is labeled as <strong>Scope is Registered</strong> this means that ESIKnife has access to the data on your account that this scope gives access to, and <strong>{{ $isGroup ? $grant->name : $grant->member->info->name }}</strong> will be able to see this data if access is granted.</li>
                            <li>If a scope is labeled as <strong>Scope is not Registered</strong>, this means that ESIKnife does access to the data that the scope provides access to and <strong>{{ $isGroup ? $grant->name : $grant->member->info->name }}</strong> will not be able to access this data either.</li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">

                                <form action="{{ route('settings.grant', ['hash' => $isGroup ? $grant->creator_id . ":" . $grant->id : $grant->id.":".$grant->hash]) }}" method="post">
                                    {{ csrf_field() }}
                                    <button type="submit" class="btn btn-success btn-block">Grant Access to Data</button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('dashboard') }}" class="btn btn-danger btn-block">Do not grant Access to Data</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
