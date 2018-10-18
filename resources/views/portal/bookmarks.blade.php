@extends('layout.index')

@section('title', 'My Bookmarks')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header text-center">
                        Unique Locations ({{ $uniqueLocations->count() }})
                    </div>
                    <div class="list-group">
                        @foreach ($uniqueLocations as $location)
                            <a href="{{ config('services.eve.urls.dotlan') }}map/{{ $location->id }}/" class="list-group-item list-group-item-action" target="_blank">
                                {{ $location->name }} ({{ $location->count }})
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                @foreach ($member->bookmarkFolders as $folder)
                    <div class="card">
                        <div class="card-header" data-toggle="collapse" data-target="#folder{{ $folder->folder_id }}">
                            <h5 class="mb-0">{{ $folder->name }} ({{ $bookmarks->where('folder_id', $folder->folder_id)->count() }})</h5>
                        </div>
                        <div id="folder{{ $folder->folder_id }}" class="collapse">
                            <div class="card-body p-0">
                                <table class="table mb-0">
                                    @forelse ($bookmarks->where('folder_id', $folder->folder_id) as $bookmark)
                                        <tr>
                                            <td>
                                                {{ $bookmark->label }}
                                            </td>
                                            <td>
                                                {{ $bookmark->notes }}
                                            </td>
                                            <td>
                                                {{ !is_null($bookmark->location) ? $bookmark->location->name : "Unknown Location ". $bookmark->location_id }}
                                            </td>
                                            <td>
                                                {{ !is_null($bookmark->creator) ? $bookmark->creator->name : "Unknown Creator ". $bookmark->creator_id }}
                                            </td>
                                            <td>
                                                {{ !is_null($bookmark->type) ? $bookmark->type->name  : "Middle of Space" }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                No Bookmarks Here
                                            </td>
                                        </tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
