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
                                    Current Refresh Token
                                </th>
                                <td>
                                    {{ str_limit(Auth::user()->refresh_token, 40) ?: '-' }}
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    Currently Authorized Scopes
                                </th>
                                <td>
                                    @if ($scopes->isNotEmpty())
                                        <ul>
                                            <li>{!! $scopes->implode("</li><li>") !!}</li>
                                        </ul>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    Expires At
                                </th>
                                <td>
                                    {{ !is_null(Auth::user()->expires) ?  Auth::user()->expires->toDayDateTimeString() . " Eve (UTC)": '-' }}
                                </td>
                            </tr>
                            @if (!is_null(Auth::user()->access_token) && !is_null(Auth::user()->refresh_token) && !is_null(Auth::user()->scopes))
                                <tr>
                                    <th>
                                        Next Refresh
                                    </th>
                                    <td>
                                        All tokens are refreshed every five minutes unless cleared
                                    </td>
                                </tr>
                            @endif

                        </table>
                    </div>
                    @if (!is_null(Auth::user()->access_token) && !is_null(Auth::user()->refresh_token) && !is_null(Auth::user()->scopes))
                        <div class="card-footer text-center">
                            <form action="{{ route('settings.token') }}" method="post">
                                {{ csrf_field() }}
                                @method('DELETE')
                                <button type="submit" name="action" value="delete" class="btn btn-danger">Delete My Token</button>
                            </form>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
