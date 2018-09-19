<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



Route::group(['middleware'=>'permission'], function () {
    Route::group(['prefix' => 'user'], function () {
        Route::post('login', 'UserController@login');

        Route::post('list',           'UserController@getList');
        Route::post('create',         'UserController@create');
        Route::post('update/{id}',    'UserController@update');
        Route::post('delete/{id}',    'UserController@delete');
        Route::post('getUserInfo',    'UserController@getUserInfo');
        Route::post('message',        'UserController@message');
        Route::post('client',         'UserController@client');
    });

    Route::group(['prefix' => 'article'], function () {
        Route::post('list',           'ArticleController@getList');
        Route::post('create',         'ArticleController@create');
        Route::post('update/{id}',    'ArticleController@update');
        Route::post('delete/{id}',    'ArticleController@delete');
        Route::post('share',     'ArticleController@share');
    });

    Route::group(['prefix' => 'articleImage'], function () {
        Route::post('delete/{id}',    'ArticleImageController@delete');
    });
});

Route::group(['prefix' => 'message'], function () {
    Route::post('sendNotice',    'MessageController@sendNotice');
    Route::post('sendSMS',      'MessageController@sendSMS');
});

Route::group(['prefix' => 'user'], function () {
    Route::post('register',    'UserController@register');
});