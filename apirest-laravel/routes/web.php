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
use App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return view('welcome');}
    
);
/*metodos http comunes
    GET:conseguir datos o recursos
    POST: guardar datos o recursos o hacer logica y devolver
    PUT: actualizar recursos o datos
    DELETE: eliminar datos o recursos
*/
//rutas de prueba
//Route::get('/usuario/pruebas', 'UserController@pruebas');
//Route::get('/categoria/pruebas', 'Categoryontroller@pruebas');
//Route::get('/entrada/pruebas', 'PostController@pruebas');

//rutas del controlador de usuario
Route::post('/api/register', 'UserController@register');
Route::post('/api/login', 'UserController@login');
Route::put('/api/user/update', 'UserController@update');
Route::post('/api/user/upload', 'UserController@upload')->middleware(ApiAuthMiddleware::class);
Route::get('/api/user/avatar/(filename)', 'UserController@getImage');
Route::get('/api/user/detail/()id', 'UserController@detail');

//Rutas del Controlador de categorias (automatico)
Route::resource('/api/category', 'CategoryController');

// Rutas del controlador de entradas (PostController)

Route::resource('/api/post', 'PostController');
Route::post('/api/post/upload', 'PostController@upload');
Route::get('/api/post/image/{filename}', 'PostController@getImage');;
