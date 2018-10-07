@extends('layout.index')

@section('title', 'Default Layout')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mt-2">My Settings</h1>
                <hr />
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3">
                @include('settings.extra.nav')
            </div>
            <div class="col-lg-9">
                @include('extra.alert')
                <div class="card">
                    <form action="{{ route('settings.token') }}" method="post">
                        <div class="card-header text-center">
                            My Settings
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <tr>
                                    <th width=25%>
                                        Current Access Token
                                    </th>
                                    <td>
                                        {{ str_limit(Auth::user()->access_token, 40) ?: '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        Currently Authorized Scopes
                                    </th>
                                    <td>
                                        <ul class="list-unstyled">
                                            @foreach (config('services.eve.scopes') as $name=>$scope)
                                                <li>
                                                    <label for="{{ $name }}">
                                                        <input type="checkbox" id="{{ $name }}" name="scopes[{{ $name }}]" {{ $scopes->containsStrict($scope) ? "checked" : "" }} /> {{ $scope }}
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="card-footer text-center">
                            {{ csrf_field() }}
                            <button type="submit" class="btn btn-info">Update My Token</button>
                            <button type="button" data-toggle="modal" data-target="#tokenDeleteModal" class="btn btn-danger">Delete My Token</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <form action="{{ route('settings.token') }}" method="post">
            <div class="modal fade" id="tokenDeleteModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Please Confirm</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>By clicking below, you acknowledge your understanding that by deleting your token you will lose access to your account and all object attached to it including grant urls, access groups, fittings, and skill lists. If you are the creator of any of these objects, they will be deleted and cannot be recovered. If you register for ESIKnife again, they will need to be recreated again. To proceed, please click the red button below, otherwise, back out now by closing this window.</p>
                        </div>
                        <div class="modal-footer">
                            {{ csrf_field() }}
                            {{ method_field('delete') }}
                            <button type="button" class="btn btn-info" data-dismiss="modal">Nevermind</button>
                            <button type="submit" class="btn btn-danger">Delete My Token</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
