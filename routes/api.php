<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AttributeGroupController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryAttributesController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DeliveryAgencyController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PreorderController;
use App\Http\Controllers\RightController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Service;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TypeOfTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebNotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $personQuery = "SELECT * FROM person WHERE user_id = :userId";
        $person = DB::selectOne($personQuery, ['userId' => $request->user()->id]);
    return [$request->user(),$person];
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
    Route::post('/new_code/{id}', [UserController::class, 'new_code'])->name('new_code');
    Route::post('/verification_code', [UserController::class, 'verification_code'])->name('verification_code');
});


    Route::group(['middleware' => 'auth:api'], function () {

        Route::prefix('cart')->group(function () {
            Route::post('/addToCart', [CartController::class, 'addToCart'])->name('cart.addToCart');
            Route::delete('/removeToCart', [CartController::class, 'removeToCart'])->name('cart.removeToCart');
            Route::get('/getUserCart', [CartController::class, 'getUserCart'])->name('cart.getUserCart');
            Route::post('/incrementQuantity', [CartController::class, 'incrementQuantity'])->name('cart.incrementQuantity');
            Route::post('/decrementQuantity', [CartController::class, 'decrementQuantity'])->name('cart.decrementQuantity');
            Route::get('/getCartItem/{ad_id}', [CartController::class, 'getCartItem'])->name('cart.getCartItem');
        });

        Route::prefix('order')->group(function () {
            Route::post('/CreateAnOrder', [OrderController::class, 'CreateAnOrder'])->name('order.CreateAnOrder');
            Route::post('/orderSingleItem/{cartItemId}', [OrderController::class, 'orderSingleItem'])->name('order.orderSingleItem');
            Route::get('/viewOrder/{orderId}', [OrderController::class, 'viewOrder'])->name('order.viewOrder');
            Route::get('/listOrders', [OrderController::class, 'listOrders'])->name('order.listOrders');
            Route::delete('/cancelOrder/{orderId}', [OrderController::class, 'cancelOrder'])->name('order.cancelOrder');
            Route::post('/deleteOrderDetail/{orderDetailId}', [OrderController::class, 'deleteOrderDetail'])->name('order.deleteOrderDetail');
            Route::post('/updateOrderDetail/{orderDetailId}', [OrderController::class, 'updateOrderDetail'])->name('order.updateOrderDetail');
        });

        Route::get('/user', [UserController::class, 'userAuth']);

        Route::prefix('users')->group(function () {
            Route::post('/new_code/{id}', [UserController::class, 'new_code'])->name('new_code');
        });

        Route::prefix('ad')->group(function () {

            Route::get('/marchand', [AdController::class, 'getMarchandAd'])->name('ad.marchand');
            Route::delete('/destroyAd/{uid}', [AdController::class, 'destroyAd'])->name('ad.destroyAdd');
        });

        Route::prefix('preorder')->group(function () {
            Route::post('/createPreorder', [PreorderController::class, 'createPreorder'])->name('preorder.createPreorder');
            Route::post('/validatePreorder/{uid}', [PreorderController::class, 'validatePreorder'])->name('preorder.validatePreorder');
            Route::post('/rejectPreorder/{uid}', [PreorderController::class, 'rejectPreorder'])->name('preorder.rejectPreorder');
            Route::get('/merchantAffectedByPreorder/{perPage}', [PreorderController::class, 'merchantAffectedByPreorder'])->name('preorder.merchantAffectedByPreorder');
            Route::get('/getValidatedPreordersWithAnswers/{preorderUid}/{perPage}', [PreorderController::class, 'getValidatedPreordersWithAnswers'])->name('preorder.getValidatedPreordersWithAnswers');
            Route::get('/getPreordersAnswerSortedByPrice/{preorderUid}/{perPage}', [PreorderController::class, 'getPreordersAnswerSortedByPrice'])->name('preorder.getPreordersAnswerSortedByPrice');
            Route::get('/getPreordersAnswerSortedByDeliveryTime/{preorderUid}/{perPage}', [PreorderController::class, 'getPreordersAnswerSortedByDeliveryTime'])->name('preorder.getPreordersAnswerSortedByDeliveryTime');
        
        });

        Route::prefix('preorder_answer')->group(function () {
            Route::post('/createPreorderAnswer/{preorderId}', [PreorderController::class, 'createPreorderAnswer'])->name('preorder.createPreorderAnswer');
            Route::post('/validatePreorderAnswer/{uid}', [PreorderController::class, 'validatePreorderAnswer'])->name('preorder.validatePreorderAnswer');
            Route::post('/rejectPreorderAnswer/{uid}', [PreorderController::class, 'rejectPreorderAnswer'])->name('preorder.rejectPreorderAnswer');
        });

        Route::prefix('review')->group(function () {
            Route::post('/write/{PreordersAnswerUid}', [PreorderController::class, 'write'])->name('review.write');
            Route::get('/answerReviews/{PreordersAnswerUid}', [PreorderController::class, 'answerReviews'])->name('preorder.answerReviews');
          
        });

    });
    Route::prefix('category')->group(function () {
        Route::post('/add', [CategoryController::class, 'add'])->name('category.add');
        Route::get('/detail/{uid}', [CategoryController::class, 'showCategoryDetail'])->name('category.detail');
        Route::get('/all', [CategoryController::class, 'getAllCategories'])->name('category.all');
        Route::get('/search', [CategoryController::class, 'searchCategory'])->name('category.search');
    });

    Route::prefix('country')->group(function () {
        Route::post('/load', [CountryController::class, 'load'])->name('country.load');
        Route::post('/add', [CountryController::class, 'add'])->name('country.add');
        Route::get('/all', [CountryController::class, 'getAllCountries'])->name('country.all');
    });

    Route::prefix('ad')->group(function () {
        Route::get('/recent/{perPage}', [AdController::class, 'getRecentAdd'])->name('ad.recent');
        Route::get('/all', [AdController::class, 'getAllAd'])->name('ad.all');
        Route::post('/storeAd', [AdController::class, 'storeAd'])->name('ad.storeAd');
        Route::post('/editAd/{uid}', [AdController::class, 'editAd'])->name('ad.editAd');
        Route::post('/uploadAdImage/{adUid}', [Service::class, 'uploadAdImage'])->name('ad.uploadAdImage');
        Route::post('/validateAd/{uid}', [AdController::class, 'validateAd'])->name('ad.validateAd');
        Route::post('/rejectAd/{uid}', [AdController::class, 'rejectAd'])->name('ad.rejectAd');
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

    // Route::prefix('right')->group(function () {
    //     Route::get('/index', [RightController::class, 'index']);
    //     Route::post('/store', [RightController::class, 'store']);
    //     Route::get('/show/{id}', [RightController::class, 'show']);
    //     Route::put('/update/{id}', [RightController::class, 'update']);
    //     Route::delete('/destroy/{id}', [RightController::class, 'destroy']);
    // });

    Route::prefix('typeoftype')->group(function () {
        Route::post('/store', [TypeOfTypeController::class, 'store']);
    });

    Route::prefix('attributeGroup')->group(function () {
        Route::post('/store', [AttributeGroupController::class, 'store']);
    });


    Route::prefix('categoryAttribute')->group(function () {
        Route::post('/store', [CategoryAttributesController::class, 'store']);
    });

    Route::prefix('file')->group(function () {
        Route::get('/getFilesByFileCode/{file}/{returnSingleFile}', [FileController::class, 'getFilesByFileCode']);
        Route::delete('/removeFile/{uid}', [Service::class, 'removeFile'])->name("file.removeFile");
    });


    Route::prefix('preorder')->group(function () {
        Route::get('/getPreorderValidated', [PreorderController::class, 'getPreorderValidated'])->name('preorder.getPreorderValidated');
        Route::get('/getPreorderPending', [PreorderController::class, 'getPreorderPending'])->name('preorder.getPreorderPending');
        Route::get('/getPreorderWitnAnswer', [PreorderController::class, 'getPreorderWitnAnswer'])->name('preorder.getPreorderWitnAnswer');
        Route::get('/getPreorderRejected', [PreorderController::class, 'getPreorderRejected'])->name('preorder.getPreorderRejected');
        Route::get('/getPreorderWithValidatedAnswers/{uid}', [PreorderController::class, 'getPreorderWithValidatedAnswers'])->name('preorder.getPreorderWithValidatedAnswers');
        Route::get('/getAuthPreorderValidated', [PreorderController::class, 'getAuthPreorderValidated'])->name('preorder.getAuthPreorderValidated');
    });

    Route::prefix('preorder_answer')->group(function () {
        Route::get('/getPreorderAnswerValidated', [PreorderController::class, 'getPreorderAnswerValidated'])->name('preorder.getPreorderAnswerValidated');
        Route::get('/getPreorderAnswerPending', [PreorderController::class, 'getPreorderAnswerPending'])->name('preorder.getPreorderAnswerPending');
        Route::get('/getPreorderAnswerRejected', [PreorderController::class, 'getPreorderAnswerRejected'])->name('preorder.getPreorderAnswerRejected');
        Route::get('/getSpecificPreorderAnswer/{uid}', [PreorderController::class, 'getSpecificPreorderAnswer'])->name('preorder.getSpecificPreorderAnswer');
    });


    Route::prefix('shop')->group(function () {
        Route::post('/updateShop/{uid}', [ShopController::class, 'updateShop'])->name('shop.updateShop');
        Route::get('/showShop/{uid}', [ShopController::class, 'showShop'])->name('shop.showShop');
        Route::post('/addShopFile/{filecodeShop}', [ShopController::class, 'addShopFile'])->name('preorder.addShopFile');
        Route::post('/updateShopFile/{uid}', [ShopController::class, 'updateShopFile'])->name('shop.updateShopFile');
        Route::post('/addCategoryToSHop/{shopId}', [ShopController::class, 'addCategoryToSHop'])->name('shop.addCategoryToSHop');
        Route::post('/becomeMerchant', [ShopController::class, 'becomeMerchant'])->name('shop.becomeMerchant');
        Route::get('/AdMerchant/{shopId}/{perPage}', [ShopController::class, 'AdMerchant'])->name('shop.AdMerchant');

        Route::get('/userShop', [ShopController::class, 'userShop'])->name('shop.userShop');
    });

    Route::prefix('role')->group(function () {
        Route::post('/update/{id}', [RoleController::class, 'update'])->name('role.update');
        Route::get('/show/{id}', [RoleController::class, 'show'])->name('role.show');
        Route::post('/store', [RoleController::class, 'store'])->name('role.store');
        Route::get('/index', [RoleController::class, 'index'])->name('role.index');
    });

    Route::prefix('permission')->group(function () {
        Route::post('/update/{id}', [PermissionController::class, 'update'])->name('permission.update');
        Route::get('/show/{id}', [PermissionController::class, 'show'])->name('permission.show');
        Route::post('/store', [PermissionController::class, 'store'])->name('permission.store');
        Route::get('/index', [PermissionController::class, 'index'])->name('permission.index');
    });


    Route::get('/returnClientIdAuth', [Service::class, 'returnClientIdAuth'])->name('service.returnClientIdAuth');