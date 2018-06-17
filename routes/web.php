<?php

Route::match(['GET'],'/', 'PublicController@home')->name('home');
Route::match(['GET'],'/about', 'PublicController@about')->name('about');
Route::match(['GET'],'/donate', 'PublicController@donate')->name('donate');


Route::match(['GET'],'/login', 'AuthController@login')->name('auth.login');
Route::match(['GET'],'/logout', 'AuthController@logout')->name('auth.logout');


Route::match(['GET'],'/sso/callback', 'SSOController@callback')->name('sso.callback');
Route::match(['GET'],'/sso/revoke', 'SSOController@revoke')->name('sso.revoke');
Route::match(['GET'],'/sso/refresh', 'SSOController@refresh')->name('sso.refresh');

Route::group(['middleware' => ['auth']], function () {

    Route::match(['GET'], '/dashboard', 'PortalController@dashboard')->name('dashboard');
    Route::match(['GET'], '/settings', 'SettingController@index')->name('settings.index');
    Route::match(['GET', 'DELETE'], '/settings/token', 'SettingController@token')->name('settings.token');
    Route::match(['GET', 'POST'], '/settings/access', 'SettingController@access')->name('settings.access');
    Route::match(['GET', 'POST'], '/welcome', 'PortalController@welcome')->name('welcome');

    Route::match(['GET'], '/{member}/overview', 'PortalController@overview')->name('overview');
    Route::match(['GET'], '/{member}/assets', 'PortalController@assets')->name('assets')->middleware('authorized:Somethign');
    Route::match(['GET'], '/{member}/bookmarks', 'PortalController@bookmarks')->name('bookmarks');
    Route::match(['GET'], '/{member}/clones', 'PortalController@clones')->name('clones');
    Route::match(['GET'], '/{member}/contacts', 'PortalController@contacts')->name('contacts');
    Route::match(['GET'], '/{member}/contracts', 'PortalController@contracts')->name('contracts');
    Route::match(['GET'], '/{member}/contract/{contract_id}', 'PortalController@contract')->name('contract.view');
    Route::match(['GET'], '/{member}/contracts/interactions', 'PortalController@contractInteractions')->name('contracts.interactions');
    Route::match(['GET'], '/{member}/mails', 'PortalController@mails')->name('mails');
    Route::match(['GET'], '/{member}/mail/{mail_id}', 'PortalController@mail')->name('mail');
    Route::match(['GET'], '/{member}/skills', 'PortalController@skills')->name('skillz');
    Route::match(['GET'], '/{member}/skills/flyable', 'PortalController@flyable')->name('skillz.flyable');
    Route::match(['GET'], '/{member}/skillqueue', 'PortalController@queue')->name('skillqueue');
    Route::match(['GET'], '/{member}/wallet/transactions', 'PortalController@wallet_transactions')->name('wallet.transactions');
    Route::match(['GET'], '/{member}/wallet/journal', 'PortalController@wallet_journal')->name('wallet.journal');
});


Route::match(['GET'], '/hack', 'HackingController@index');
