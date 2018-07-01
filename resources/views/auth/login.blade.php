@extends('layout.index')

@section('title', 'ESIK Login')

@section('content')
    <!-- Page Content -->
    <div class="container">
        <div class="row">
            <div class="col-lg-6 offset-md-3 mt-3">
                <div class="card">
                    <div class="card-header text-center">
                        Welcome to ESI Knife
                    </div>
                    <div class="card-body">
                        @include('extra.alert')
                        <p>Welcome to ESI Knife. To get started, use the button below to login using CCP's SSO. From there, we will get you account setup.</p>
                        <a href="{{ $ssoUrl }}" class="text-center">
                            <img src="https://web.ccpgamescdn.com/eveonlineassets/developers/eve-sso-login-white-large.png" class="rounded mx-auto d-block"/>
                        </a>
                    </div>
                </div>
                <h4 class="mt-2">Patch Notes</h4>
                <hr class="mt-0" />
                <div id="accordion">
                    <div class="card">
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#v022a_beta">
                            <span>
                                2018-07-01 - v0.22a-beta
                            </span>
                        </div>
                        <div id="v022a_beta" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                <p>
                                    The following addresses the bugs and feature requests that were released in the <a href="{{ config('services.bitbucket.urls.commit') }}/tag/v0.22a-beta">release</a>. If you have any question, please reference the #TalkWithTheDeveloper section of the about page.
                                </p>
                                <ul>
                                    <li>
                                        Updated this page with these patch notes
                                    </li>
                                    <li>
                                        Fixed Issue #26 addressing a where a character utilizing a grant url to authorize another character access to their data. The system was not taking into consideration the scopes that the user had grant the application to access when registered and was just blanket approving every scope in the grant. This resulted in access to pages on the user that the user never granted access to when registering with the site.
                                    </li>
                                    <li>
                                        Fixed a typo on this page, somewhere.
                                    </li>
                                    <li>
                                        Added the ability generate a refresh token for an "Admin Character" that will later be used to query the wallet to detect Wallet Transactions and donations.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#v021_beta">
                            <span>
                                2018-06-23 - v0.21-beta
                            </span>
                        </div>
                        <div id="v021_beta" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                <p>
                                    The following addresses the bugs and feature requests that were released in the <a href="{{ config('services.bitbucket.urls.commit') }}/tag/v0.21-beta">release</a>. If you have any question, please reference the #TalkWithTheDeveloper section of the about page.
                                </p>
                                <ul>
                                    <li>
                                        Updated this page with these patch notes
                                    </li>
                                    <li>
                                        Added Link to URL management page on the Setting Navigation Menu
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#v020_beta">
                            <span>
                                2018-06-23 - v0.20-beta
                            </span>
                        </div>
                        <div id="v020_beta" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                <p>
                                    The following addresses the bugs and feature requests that were released in the <a href="{{ config('services.bitbucket.urls.commit') }}/tag/v0.20-beta">release</a>. If you have any question, please reference the #TalkWithTheDeveloper section of the about page.
                                </p>
                                <ul>
                                    <li>
                                        Updated this page with these patch notes
                                    </li>
                                    <li>
                                        Added Support for Shareable URLs
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="headingOne" data-toggle="collapse" data-target="#v013_beta">
                            <span>
                                2018-06-20 - v0.13-beta
                            </span>
                        </div>
                        <div id="v013_beta" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                            <div class="card-body">
                                <p>
                                    The following addresses the bugs and feature requests that were released in the <a href="{{ config('services.bitbucket.urls.commit') }}/tag/v0.13-beta">release</a>. If you have any question, please reference the #TalkWithTheDeveloper section of the about page.
                                </p>
                                <ul>
                                    <li>
                                        Fixed several issues with invalid data being displayed when viewing another characters data.
                                    </li>
                                    <li>
                                        Fixed an issue with Skills where the Skill Groups Training were displaying the names of skill rather than the names of groups
                                    </li>
                                    <li>
                                        Fixed an issue with the About Us page where click the title of one section would open another section
                                    </li>
                                    <li>
                                        Fixed a display issue when viewing the about page on Mobile, longer titles would trail of the screen
                                    </li>
                                    <li>
                                        Fixed a display issue when viewing the about page on Mobile, longer titles would trail of the screen
                                    </li>
                                     <li>
                                         Added a filter to the contacts so that NPC Agent will no longer show up when viewing contacts. The viewer can toggle these back on by clicking the supplied button.
                                     </li>
                                     <li>
                                         Fixed an issue with the assignee_type column on the contracts table preventing Alliance Contract from being inserted into the table due a 1265 Warning Error. This is because that column has an enum setup on it and the string alliance was not in the enum.
                                     </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
