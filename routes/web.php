<?php

use Illuminate\Support\Facades\Route;

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

Route::view('/', 'welcome');

Route::view('/login', 'login')->name('login');

Route::get('/citylist', 'OrdersController@CityList')->name('citylist');

Route::post('/fulfilment', 'OrdersController@fulfilment')->name('fulfilment');

Route::post('/partial_fulfilment', 'OrdersController@PartialFulfilment')->name('partial_fulfilment');

Route::get('slip', 'OrdersController@PrintSlip')->name('slip');

Route::get('PartialShipment', 'OrdersController@PartialShipment')->name('PartialShipment');

Route::group(['middleware' => 'AuthUser'], function(){
    Route::get('/orders', 'OrdersController@index')->name('orders');  

});

Route::get('user_verification', 'AuthController@user_verification');

Route::group(['prefix' => 'auth'], function () {
    
  Route::get('install', 'MainController@install');

  Route::get('load', 'MainController@load');

  Route::get('uninstall', function () {
    echo 'uninstall';
    return app()->version();
  });

  Route::get('remove-user', function () {
    echo 'remove-user';
    return app()->version();
  });
});

Route::any('/bc-api/{endpoint}', 'MainController@proxyBigCommerceAPIRequest')
  ->where('endpoint', 'v2\/.*|v3\/.*');
