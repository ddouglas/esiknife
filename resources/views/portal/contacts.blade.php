@extends('layout.index')

@section('title', 'My Contacts')

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="col-lg-4 offset-lg-4">
            <div class="list-group">
                @foreach (Auth::user()->contacts as $contact)
                @if ($contact->standing == 10)
                    <a class="list-group-item list-group-item-secondary" href="{{ config('services.eve.urls.km') }}{{ $contact->contact_type }}/{{ $contact->contact_id }}/" target="_blank">
                @elseif ($contact->standing == 5)
                    <a class="list-group-item list-group-item-info" href="{{ config('services.eve.urls.km') }}{{ $contact->contact_type }}/{{ $contact->contact_id }}/" target="_blank">
                @elseif ($contact->standing == 0)
                    <a class="list-group-item list-group-item-default" href="{{ config('services.eve.urls.km') }}{{ $contact->contact_type }}/{{ $contact->contact_id }}/" target="_blank">
                @elseif ($contact->standing == -5)
                    <a class="list-group-item list-group-item-warning" href="{{ config('services.eve.urls.km') }}{{ $contact->contact_type }}/{{ $contact->contact_id }}/" target="_blank">
                @elseif ($contact->standing == -10)
                    <a class="list-group-item list-group-item-danger" href="{{ config('services.eve.urls.km') }}{{ $contact->contact_type }}/{{ $contact->contact_id }}/" target="_blank">
                @endif
                        <div class="media">
                            @if($contact->contact_type === "character")
                                <img class="mr-3 rounded img-fluid" src="{{ config('services.eve.urls.img') }}/Character/{{ $contact->contact_id }}_64.jpg" />
                            @else
                                <img class="mr-3 rounded img-fluid" src="{{ config('services.eve.urls.img') }}/{{ ucfirst($contact->contact_type) }}/{{ $contact->contact_id }}_64.png" />
                            @endif
                            <div class="media-body">
                                <h5 class="mt-0">{{ !is_null($contact->info) ? $contact->info->name : "Unknown Contact ". $contact->contact_id }}</h5>
                                @if (!is_null($contact->label_ids))
                                    @foreach (json_decode($contact->label_ids) as $label)
                                        <span class="badge badge-primary">{{ Auth::user()->contact_labels->keyBy('label_id')->get($label)->label_name }}</span>
                                    @endforeach
                                    <br />
                                @endif
                                Standing: {{ $contact->standing }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection
