<?php

Route::match(['GET'],'/', 'PublicController@home')->name('home');


Route::match(['GET'],'/login', 'AuthController@login')->name('auth.login');
Route::match(['GET'],'/logout', 'AuthController@logout')->name('auth.logout');


Route::match(['GET'],'/sso/callback', 'SSOController@callback')->name('sso.callback');
Route::match(['GET'],'/sso/revoke', 'SSOController@revoke')->name('sso.revoke');
Route::match(['GET'],'/sso/refresh', 'SSOController@refresh')->name('sso.refresh');

Route::group(['middleware' => ['guest']], function () {
    Route::match(['GET', 'POST'], '/welcome', 'PortalController@welcome')->name('welcome');
});

Route::group(['middleware' => ['auth']], function () {

    Route::match(['GET'], '/dashboard', 'PortalController@dashboard')->name('dashboard');
    Route::match(['GET'], '/settings', 'SettingController@index')->name('settings.index');
    Route::match(['GET', 'DELETE'], '/settings/token', 'SettingController@token')->name('settings.token');
    Route::match(['GET', 'POST'], '/settings/access', 'SettingController@access')->name('access');

    Route::match(['GET'], '/{id}/overview', 'PortalController@overview')->name('overview');
    Route::match(['GET'], '/{id}/assets', 'PortalController@assets')->name('assets');
    Route::match(['GET'], '/{id}/bookmarks', 'PortalController@bookmarks')->name('bookmarks');
    Route::match(['GET'], '/{id}/clones', 'PortalController@clones')->name('clones');
    Route::match(['GET'], '/{id}/contacts', 'PortalController@contacts')->name('contacts');
    Route::match(['GET'], '/{id}/contracts', 'PortalController@contracts')->name('contracts');
    Route::match(['GET'], '/{id}/contract/{contract_id}', 'PortalController@contract')->name('contract.view');
    Route::match(['GET'], '/{id}/contracts/interactions', 'PortalController@contractInteractions')->name('contracts.interactions');
    Route::match(['GET'], '/{id}/mails', 'PortalController@mails')->name('mails');
    Route::match(['GET'], '/{id}/mail/{mail_id}', 'PortalController@mail')->name('mail');
    Route::match(['GET'], '/{id}/skills', 'PortalController@skills')->name('skillz');
    Route::match(['GET'], '/{id}/skills/flyable', 'PortalController@flyable')->name('skillz.flyable');
    Route::match(['GET'], '/{id}/skillqueue', 'PortalController@queue')->name('skillqueue');
    Route::match(['GET'], '/{id}/wallet/transactions', 'PortalController@wallet_transactions')->name('wallet.transactions');
    Route::match(['GET'], '/{id}/wallet/journal', 'PortalController@wallet_journal')->name('wallet.journal');
});


Route::match(['GET'], '/hack', 'HackingController@index');
