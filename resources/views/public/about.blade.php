@extends('layout.index')

@section('title', 'About ESIKnife')

@section('content')
    <!-- Page Content -->
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-md-2 mt-3">
                <div class="card">
                    <div class="card-header text-center">
                        About Esi Knife
                    </div>
                    <div class="card-body">
                        <p>
                            First off, thank you for interest in ESI Knife. This page is here to answer a few question about this application. You can always talk to me directly by joined the #esi channel on the Tweetfleet Slack. Information on how to join is included below. If there is a question that you have that is not answered here, please send a mail in game to <a href="https://evewho.com/pilot/ESIKnife+Admin" target="_blank">ESIKnife Admin</a>
                        </p>
                    </div>
                    <div id="accordion">
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#collapse1">
                            <h5 class="mb-0">
                                <button class="btn btn-link">
                                    Who are you?
                                </button>
                            </h5>
                        </div>
                        <div id="collapse1" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                My main's name is David Davaham. I have an alt named ESIKnife Admin who is the CEO of the corporation The ESIKnife Corporation. Any correspondants regarding ESIKnife to our users will come from ESIKnife Admin.<br /><br />I have been developing applications in my free time for about ten years now.
                            </div>
                        </div>
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#collapse2">
                            <h5 class="mb-0">
                                <button class="btn btn-link">
                                    What is ESIKnife
                                </button>
                            </h5>
                        </div>
                        <div id="collapse2" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                ESIKnife is a version of the famous Jackknife application that was built on top of CCP's XML/Crest API. This one is built on top of CCP's new API, the Eve Swagger Interface, better known as ESI (pronounced as "easy").
                            </div>
                        </div>
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#collapse4">
                            <h5 class="mb-0">
                                <button class="btn btn-link">
                                    Okay, So What is Jackknife?
                                </button>
                            </h5>
                        </div>
                        <div id="collapse4" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                As I mentioned, Jackknife was an application that was built ontop of CCP's XML/Crest API. It allows a player to share information about their character(s) with any other character in game without having to give away usernames and passwords or any tedious screenshots/copy pasta. It is/was a very common tool used amount corporation recruiters/ceo's to audit/evaluate a character prior to allowing them to join their corporation.
                            </div>
                        </div>
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#collapse5">
                            <h5 class="mb-0">
                                <button class="btn btn-link">
                                    Why did you build ESIKnife
                                </button>
                            </h5>
                        </div>
                        <div id="collapse5" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                ESIKnife was built for two reasons.
                                <ol>
                                    <li>
                                        I built ESIKnife as a replacement for Jackknife. No viable replacement's were publicaly in the works as the deadline for the shutdown of the XML/Crest API's approached. About a month before this, I started work on ESIKnife. Real life prevented me from finishing the application on time and some serious design flaw's in the first version pushed the release of the application back even further.
                                    </li>
                                    <li>
                                        I built it because it was a challenge. I do not program IRL professionally. All of my programming is purely done as a hobby in my free time. Some would say it is a bad addiction that I have. Tha tis good for you guys, sometimes bad for me :-)
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
