@extends('layout.index')

@section('title', 'Sharing My Data')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        Access Urls
                    </div>
                    <div class="card-body">
                        Access Urls are links that you can send to another character that they can click on, acknowledge the access that is being granted and have you added to their account as an accessor. This method is meant to ease the process of auditing a character.
                    </div>
                </div>
                <hr />
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                @include('settings.extra.nav')
            </div>
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        My Current Urls
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered m-0">
                            <tr>
                                <th>
                                    Hash
                                </th>
                                <th>
                                    Copy Url
                                </th>
                                <th>
                                    Scopes
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                            @forelse (Auth::user()->urls as $url)
                                <tr>
                                    <td class="align-middle">
                                        <span>{{ $url->hash }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <span><a href="#" class="copyMe" data-toggle="tooltip" title="{{ route('settings.grant', ['grant' => Auth::user()->id.":".$url->hash]) }}" data-clipboard-text="{{ route('settings.grant', ['grant' => Auth::user()->id.":".$url->hash]) }}">Click To Copy URL</a></span>
                                    </td>
                                    <td>
                                        @forelse($url->scopes as $scope)
                                            <li>{{ $scope }}</li>
                                        @empty
                                            <li>There are no scopes associated with this url</li>
                                        @endforelse
                                    </td>
                                    <td class="align-middle">
                                        <form action="{{ route('settings.urls', ['hash' => $url->hash]) }}" method="post">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                                            <button type="submit" class="btn btn-danger btn-block">Delete This URL</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        You do not currently have any urls setup
                                    </td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary btn-sm float-right" data-toggle="collapse" data-target="#urlGenerator">Generate New URL</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        <div class="collapse" id="urlGenerator">
                            <div class="card mt-2">
                                <div class="card-header">
                                    Url Generator
                                </div>
                                <div class="card-body">
                                    @include('extra.alert')
                                    <form action="{{ route('settings.urls') }}" method="post">
                                        <div class="form-group">
                                            <label for="name">Name of URL (optional)</label>
                                            <input type="text" name="name" id="name" class="form-control" placeholder="Url for Corporation Recruitment" value="{{ old('name') }}"/>
                                        </div>
                                        <div class="form-group">
                                            <label>Scopes Requesting:</label> <a href="#" id="all">[Select All]</a>
                                            <ul class="list-unstyled">
                                                @foreach (config('services.eve.scopes') as $key => $scope)
                                                    <li>
                                                        <label for="scopes[{{ $key }}]">
                                                            <input type="checkbox" name="scopes[{{ $key }}]" id="scopes[{{ $key }}]" class="item" @if(isset(old('scopes')[$key])){{ "checked" }} @endif /> {{ $scope }}
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div class="form-group text-center mb-0">
                                            {{ csrf_field() }}
                                            <button type="submit" class="btn btn-primary">Generate URL</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js" integrity="sha256-Daf8GuI2eLKHJlOWLRR/zRy9Clqcj4TUSumbxYH9kGI=" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function () {
            $('#all').on('click', function(){
                $(':checkbox.item').prop('checked', true);
            });

            @if (isset($errors) && count($errors) > 0)
                $('#urlGenerator').collapse({
                    toggle: true
                });
            @endif
        });
        $(".copyMe").tooltip();
        var clipeboard = new Clipboard('.copyMe')
    </script>
@endsection
