@extends('layout.index')

@section('title', 'Welcome To ESIKnife')

@section('content')
    <div class="container">
        <div class="row mt-3">
            <div class="col-lg-3">
                <img src="{{ config('services.eve.urls.img') }}/Character/{{ Auth::user()->id }}_512.jpg" class="img-fluid rounded mx-auto d-block" />
            </div>
            <div class="col-lg-9">
                <h1 class="text-center">Welcome to ESI Knife {{ Auth::user()->info->name }}</h1>
                <hr />
                <p>
                    You are here because you're either interested in viewing your data out-of-game in a secure environment or a recruiter has instructed you come to our site and register as part of their recruitment process so that they can make sure that you are not a spy!. No worries, we'll help you get setup. It is really simple. Below are the scopes that are currently supported by our site, meaning these are the piece of data that we can currently pull from ESI. More are added every day. Please check the scopes for the data that you are interested in viewing and then click the submit button below them.
                </p>
                <h3 class="mb-1">Select the Scopes!</h3>
                <hr />
                @include('extra.alert')
                <form action="{{ route('welcome') }}" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <div class="float-right">
                                        <a href="#" id="all">[Select All]</a>
                                    </div>
                                    <strong>Character Information</strong>
                                </li>
                                <label for="readCharacterAssets" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterAssets" name="scopes[readCharacterAssets]" class="item" /> <span class="ml-2">Read Character Assets</span>
                                    </li>
                                </label>

                                <label for="readCharacterBookmarks" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterBookmarks" name="scopes[readCharacterBookmarks]" class="item" /> <span class="ml-2">Read Character Bookmarks</span>
                                    </li>
                                </label>

                                <label for="readCharacterClones" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterClones" name="scopes[readCharacterClones]" class="item" /> <span class="ml-2">Read Character Clones</span>
                                    </li>
                                </label>

                                <label for="readCharacterContacts" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterContacts" name="scopes[readCharacterContacts]" class="item" /> <span class="ml-2">Read Character Contacts & Standings</span>
                                    </li>
                                </label>

                                <label for="readCharacterContracts" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterContracts" name="scopes[readCharacterContracts]" class="item" /> <span class="ml-2">Read Character Contracts</span>
                                    </li>
                                </label>

                                <label for="readCharacterImplants" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterImplants" name="scopes[readCharacterImplants]" class="item" /> <span class="ml-2">Read Character Implants</span>
                                    </li>
                                </label>

                                <label for="readCharacterLocation" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterLocation" name="scopes[readCharacterLocation]" class="item" /> <span class="ml-2">Read Character Location</span>
                                    </li>
                                </label>

                                <label for="readCharacterMails" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterMails" name="scopes[readCharacterMails]" class="item" /> <span class="ml-2">Read Character Mails</span>
                                    </li>
                                </label>

                                <label for="readCharacterSkills" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterSkills" name="scopes[readCharacterSkills]" class="item" /> <span class="ml-2">Read Character Skills</span>
                                    </li>
                                </label>

                                <label for="readCharacterSkillQueue" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterSkillQueue" name="scopes[readCharacterSkillQueue]" class="item" /> <span class="ml-2">Read Character Skill Queue</span>
                                    </li>
                                </label>

                                <label for="readCharacterShip" class="mb-0">
                                    <li class="list-group-item py-auto ">
                                        <input type="checkbox" id="readCharacterShip" name="scopes[readCharacterShip]" class="item" /> <span class="ml-2">Read Character Ship</span>
                                    </li>
                                </label>

                                <label for="readCharacterWallet" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readCharacterWallet" name="scopes[readCharacterWallet]" class="item" /> <span class="ml-2">Read Character Wallet</span>
                                    </li>
                                </label>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <ul class="list-group">
                                <li class="list-group-item text-center"><strong>Utility Classes</strong></li>
                                <label for="readUniverseStructures" class="mb-0">
                                    <li class="list-group-item py-auto">
                                        <input type="checkbox" id="readUniverseStructures" name="scopes[readUniverseStructures]" class="item" /> <span class="ml-2">Read Structure Names</span>
                                    </li>
                                </label>
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
