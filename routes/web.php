<?php

Route::match(['GET'],'/', 'PublicController@home')->name('home');


Route::match(['GET'],'/login', 'AuthController@login')->name('auth.login');
Route::match(['GET'],'/logout', 'AuthController@logout')->name('auth.logout');


Route::match(['GET'],'/sso/callback', 'SSOController@callback')->name('sso.callback');
Route::match(['GET'],'/sso/revoke', 'SSOController@revoke')->name('sso.revoke');
Route::match(['GET'],'/sso/refresh', 'SSOController@refresh')->name('sso.refresh');

Route::match(['GET', 'POST'], '/welcome', 'PortalController@welcome')->name('welcome');

Route::group(['middleware' => ['auth']], function () {
    Route::match(['GET', 'POST'], '/dashboard', 'PortalController@dashboard')->name('dashboard');
});


Route::match(['GET'], '/hack', 'HackingController@index');
