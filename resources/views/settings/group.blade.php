@extends('layout.index')

@section('title', 'My Groups')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        <div class="row">
            <div class="col-lg-3">
                @include('settings.extra.nav')
            </div>
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header text-center">
                        {{ $group->name }}
                    </div>
                    <div class="card-body">
                        {{ $group->description ?? "This group does not have a description set" }}
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" name="url" id="url" class="form-control" value="{{ route('settings.grant', ['hash' => $group->creator_id . ":" . $group->id]) }}" disabled/>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-secondary copyMe" data-clipboard-text="{{ route('settings.grant', ['hash' => $group->creator_id . ":" . $group->id]) }}">Copy Url</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-4 offset-lg-4 col-md-12">
                                <form action="{{ route('settings.group', ['group' => $group->id]) }}" method="post">
                                    {{ csrf_field() }}
                                    {{ method_field('DELETE') }}
                                    <button type="submit" class="btn btn-danger btn-block">Delete This Group</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        @include('extra.alert')
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header text-center">
                                Requested Scopes
                            </div>
                            <div class="list-group">
                                @foreach ($group->scopes as $value)
                                    <div class="list-group-item">
                                        {{ $value }}
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header text-center">
                                Group Members
                            </div>
                            <div class="list-group">
                                @forelse ($group->members as $member)
                                    <div class="list-group-item">
                                        @if ($group->creator_id != $member->id)
                                            <div class="float-right">
                                                <form action="{{ route('settings.group', ['group' => $group->id]) }}" method="post">
                                                    {{ csrf_field() }}
                                                    <input type="hidden" name="id" value="{{ $member->id }}" />
                                                    <input type="hidden" name="action" value="remove" />
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                        <div class="media mt-0">
                                            <img src="{{ config('services.eve.urls.img') }}/Character/{{ $member->id }}_64.jpg" class="rounded img-fluid mr-3" />
                                            <div class="media-body align-center">
                                                {{ $member->info->name }}
                                            </div>
                                        </div>

                                    </div>
                                @empty
                                    <div class="list-group-item">
                                        This group does not have any members assigned to it
                                    </div>
                                @endforelse
                                <form action="{{ route('settings.group', ['group' => $group->id]) }}" method="post">
                                    <input type="hidden" name="action" value="search" />
                                    {{ csrf_field() }}
                                    <div class="list-group-item">
                                        <div class="input-group">
                                            <input type="text" name="name" id="name" class="form-control" placeholder="Type Member Name to Add to Group"/>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-secondary">Add</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                @if(isset($results) && $results->count() > 0)
                                    @foreach($results as $result)
                                        <form action="{{ route('settings.group', ['group' => $group->id]) }}" method="post">
                                            <div class="list-group-item">
                                                <div class="float-right">
                                                    {{ csrf_field() }}
                                                    <input type="hidden" name="id" value="{{ $result->get('id') }}" />
                                                    <input type="hidden" name="action" value="add" />
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </div>
                                                <div class="media mt-0">
                                                    <img src="{{ config('services.eve.urls.img') }}/Character/{{ $result->get('id') }}_64.jpg" class="rounded img-fluid mr-3" />
                                                    <div class="media-body align-center">
                                                        {{ $result->get('name') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    @endforeach
                                    <div class="card-footer">
                                        <a href="{{ route('settings.access') }}" class="btn btn-danger btn-block">Clear Results</a>
                                    </div>
                                @endif
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
        var clipboard = new Clipboard('.copyMe')
    </script>
@endsection
