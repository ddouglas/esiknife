@extends('layout.index')

@section('title', $member->info->name ."'s Contacts")

@section('content')
    <div class="container">
        @include('portal.extra.header')
        @include('portal.extra.nav')
        <div class="row">
            <div class="col-12">
                @if (Request::has('npc'))
                    <a href="{{ route('contacts', ['id' => $member->id]) }}" type="submit" class="mb-3 btn btn-primary">Hide NPC Contacts</a>
                @else
                    <a href="{{ route('contacts', ['id' => $member->id, 'npc' => true]) }}" type="submit" class="mb-3 btn btn-primary">View NPC Contacts</a>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header text-center">
                        Standings Filter
                    </div>
                    <div class="list-group">
                        <a href="{{ route('contacts', ['member' => $member->id, 'standing' => 10]) }}" class="list-group-item list-group-item-action">Standings 10</a>
                        <a href="{{ route('contacts', ['member' => $member->id, 'standing' => 5]) }}" class="list-group-item list-group-item-action">Standings 5</a>
                        <a href="{{ route('contacts', ['member' => $member->id, 'standing' => 0]) }}" class="list-group-item list-group-item-action">Standings 0</a>
                        <a href="{{ route('contacts', ['member' => $member->id, 'standing' => -5]) }}" class="list-group-item list-group-item-action">Standings -5</a>
                        <a href="{{ route('contacts', ['member' => $member->id, 'standing' => -10]) }}" class="list-group-item list-group-item-action">Standings -10</a>
                        <a href="{{ route('contacts', ['member' => $member->id]) }}" class="list-group-item list-group-item-action">No Filter</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
            @foreach($member->contacts->sortByDesc('standing') as $contact)
                    <a class="list-group-item" href="{{ config('services.eve.urls.km') }}{{ $contact->contact_type }}/{{ $contact->contact_id }}/" target="_blank">
                        <div class="media">
                            @if($contact->contact_type === "character")
                                <img class="mr-3 rounded img-fluid" src="{{ config('services.eve.urls.img') }}/Character/{{ $contact->contact_id }}_64.jpg" />
                            @else
                                <img class="mr-3 rounded img-fluid" src="{{ config('services.eve.urls.img') }}/{{ ucfirst($contact->contact_type) }}/{{ $contact->contact_id }}_64.png" />
                            @endif
                            <div class="media-body">
                                <h5 class="mt-0">{{ !is_null($contact->info) ? $contact->info->name : "Unknown Contact ". $contact->contact_id }}</h5>
                                @if ($contact->standing == 10)
                                    <span class="badge badge-pill badge-info">Standing: {{ $contact->standing }}</span>
                                @elseif ($contact->standing == 5)
                                    <span class="badge badge-pill badge-light">Standing: {{ $contact->standing }}</span>
                                @elseif ($contact->standing == 0)
                                    <span class="badge badge-pill badge-secondary">Standing: {{ $contact->standing }}</span>
                                @elseif ($contact->standing == -5)
                                    <span class="badge badge-pill badge-warning">Standing: {{ $contact->standing }}</span>
                                @elseif ($contact->standing == -10)
                                    <span class="badge badge-pill badge-danger">Standing: {{ $contact->standing }}</span>
                                @endif
                                @if (!is_null($contact->label_ids))
                                    @foreach (json_decode($contact->label_ids) as $label)
                                        <span class="badge badge-light">{{ $member->contact_labels->keyBy('label_id')->get($label)->label_name }}</span>
                                    @endforeach
                                    <br />
                                @endif

                            </div>
                        </div>
                    </a>
            @endforeach
        </div>
    </div>
    </div>
@endsection
