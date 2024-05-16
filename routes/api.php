<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AttributeGroupController;
use App\Http\Controllers\CategoryAttributesController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DeliveryAgencyController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RightController;
use App\Http\Controllers\TypeOfTypeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('users')->group(function () {
    Route::post('/register', [UserController::class, 'register'])->name('register');
    Route::get('/getUser', [UserController::class, 'getUser'])->name('getUser');
    Route::get('/getUserLogin', [UserController::class, 'getUserLogin'])->name('getUserLogin');
    Route::get('/getUserLogout', [UserController::class, 'getUserLogout'])->name('getUserLogout');
    Route::post('/login', [UserController::class, 'login'])->name('login');
    Route::post('/logout', [UserController::class, 'logout'])->name('logout');
    Route::post('/restrictedUser', [UserController::class, 'restrictedUser'])->name('restrictedUser');
    Route::post('/validateEmail', [UserController::class, 'validateEmail'])->name('validateEmail');
    Route::post('/updatePassword', [UserController::class, 'updatePassword'])->name('updatePassword');
    Route::post('/password_recovery_start_step', [UserController::class, 'password_recovery_start_step'])->name('password_recovery_start_step');
    Route::post('/password_recovery_end_step', [UserController::class, 'password_recovery_end_step'])->name('password_recovery_end_step');
});


    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('/user', [UserController::class, 'userAuth']);

        Route::prefix('users')->group(function () {
        });

        Route::prefix('ad')->group(function () {

            Route::get('/marchand', [AdController::class, 'getMarchandAd'])->name('ad.marchand');
            Route::delete('/destroyAd/{uid}', [AdController::class, 'destroyAd'])->name('ad.destroyAdd');
        });

       
    });
    Route::prefix('category')->group(function () {
        Route::post('/add', [CategoryController::class, 'add'])->name('category.add');
        Route::get('/detail/{uid}', [CategoryController::class, 'showCategoryDetail'])->name('category.detail');
        Route::get('/all', [CategoryController::class, 'getAllCategories'])->name('category.all');
        Route::get('/search', [CategoryController::class, 'searchCategory'])->name('category.search');
    });

    Route::prefix('country')->group(function () {
        Route::post('/add', [CountryController::class, 'add'])->name('country.add');
        Route::get('/all', [CountryController::class, 'getAllCountries'])->name('country.all');
    });

    Route::prefix('ad')->group(function () {
        Route::get('/recent/{perPage}', [AdController::class, 'getRecentAdd'])->name('ad.recent');
        Route::get('/all', [AdController::class, 'getAllAd'])->name('ad.all');
        Route::post('/storeAd', [AdController::class, 'storeAd'])->name('ad.storeAd');
        Route::post('/editAd/{uid}', [AdController::class, 'editAd'])->name('ad.editAd');
    });

    Route::prefix('deliveryAgency')->group(function () {
        Route::post('/add/{id}', [DeliveryAgencyController::class, 'add'])->name('deliveryAgency.add');
    });

    Route::prefix('favorite')->group(function () {
        Route::post('/addAdToFavorite', [FavoriteController::class, 'addAdToFavorite'])->name('favorite.addAdToFavorite');
        Route::get('/GetFavoritesAd', [FavoriteController::class, 'GetFavoritesAd'])->name('favorite.GetFavoritesAd');
        Route::delete('/RemoveAdFromFavoriteList/{id}', [FavoriteController::class, 'RemoveAdFromFavoriteList'])->name('favorite.RemoveAdFromFavoriteList');
        Route::get('/all', [FavoriteController::class, 'all'])->name('favorite.all');
    });

    Route::prefix('right')->group(function () {
        Route::get('/index', [RightController::class, 'index']);
        Route::post('/store', [RightController::class, 'store']);
        Route::get('/show/{id}', [RightController::class, 'show']);
        Route::put('/update/{id}', [RightController::class, 'update']);
        Route::delete('/destroy/{id}', [RightController::class, 'destroy']);
    });

    Route::prefix('typeoftype')->group(function () {
        Route::post('/store', [TypeOfTypeController::class, 'store']);
    });

    Route::prefix('attributeGroup')->group(function () {
        Route::post('/store', [AttributeGroupController::class, 'store']);
    });


    Route::prefix('categoryAttribute')->group(function () {
        Route::post('/store', [CategoryAttributesController::class, 'store']);
    });