@extends('layout.index')

@section('title', 'Sharing My Data')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        <div class="row">
            <div class="col-lg-3">
                @include('settings.extra.nav')
            </div>
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        Access Groups
                    </div>
                    <div class="card-body">
                        Access Groups are groups of members that a character can grant access to instead of just a single member. The group has its own url that is sent out and the group is listed on the dashboard instead of the character(s) that it grants access to.
                    </div>
                    @if ($errors->count() > 0)
                        <div class="card-body">
                            @include('extra.alert')
                        </div>
                    @endif

                    <div class="card-body p-0">
                        <table class="table table-bordered m-0">
                            <tr>
                                <th width=35%>
                                    Name (If Applicable)
                                </th>
                                <th width=25%>
                                    Copy Url
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                            @forelse (Auth::user()->groups as $group)
                                <tr>
                                    <td class="align-middle">
                                        <span>{{ $group->name ?: "N/A" }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <span><a href="#" class="copyMe" data-toggle="tooltip" title="{{ route('settings.grant', ['hash' => $group->creator_id . ":" . $group->id]) }}" data-clipboard-text="{{ route('settings.grant', ['hash' => $group->creator_id . ":" . $group->id]) }}">Click To Copy URL</a></span>
                                    </td>
                                    <td class="align-middle">
                                        @if ($group->creator_id == Auth::user()->id)
                                            <a href="{{ route('settings.group', ['id' => $group->id]) }}" class="btn btn-primary btn-block">Url Admin</a>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        You have not created any groups yet.
                                    </td>
                                </tr>
                            @endforelse
                        </table>

                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary btn-sm float-right" data-toggle="collapse" data-target="#urlGenerator">Create New Group</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        <div class="collapse @if(Auth::user()->groups->count() == 0) {{ "show" }} @endif" id="urlGenerator">
                            <div class="card mt-2">
                                <div class="card-header">
                                    Group Creator
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('settings.groups') }}" method="post">
                                        <div class="form-group">
                                            <label for="name">Name of Group</label>
                                            <input type="text" name="name" id="name" class="form-control" placeholder="Url for Corporation Recruitment" value="{{ old('name') }}"/>
                                        </div>
                                        <div class="form-group">
                                            <label for="name">Group Description (optional)</label>
                                            <textarea type="text" name="description" id="description" class="form-control" placeholder="Group of Recruiters that will assess you account for recruitment" value="">{{ old('description') }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Scopes The Group Needs Access To:</label> <a href="#" id="all">[Un/Select All]</a>
                                            <ul class="list-unstyled">
                                                @foreach (config('services.eve.scopes') as $key => $scope)
                                                    <li>
                                                        <label for="scopes[{{ $key }}]">
                                                            <input type="checkbox" name="scopes[{{ $key }}]" id="scopes[{{ $key }}]" class="item" @if(isset(old('scopes')[$key])){{ "checked" }} @endif /> {{ $scope['scope'] }}
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
                var checkboxes = $(':checkbox.item');
                checkboxes.prop('checked', !checkboxes.prop('checked'));
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
