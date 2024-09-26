<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AdDetailController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttributeGroupController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryAttributesController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\CommissionWalletController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DeliveryAgencyController;
use App\Http\Controllers\DeliveryAgentZoneController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OngingTradeStageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OtpPasswordForgottenController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PreorderController;
use App\Http\Controllers\RightController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Service;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TradeChatController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\TradeStageController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TypeOfTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebNotificationController;
use App\Http\Controllers\ZoneController;
use App\Models\DeliveryAgentZone;
use App\Models\File;
use App\Services\OngingTradeStageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Ramsey\Uuid\Uuid;

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
    Route::post('/password_recovery_second_step', [UserController::class, 'password_recovery_second_step'])->name('password_recovery_second_step');
    Route::post('/password_recovery_end_step/{email}', [UserController::class, 'password_recovery_end_step'])->name('password_recovery_end_step');
    Route::post('/disabledUser/{uid}', [UserController::class, 'disabledUser'])->name('disabledUser');
    // Route::post('/enabledUser/{uid}', [UserController::class, 'enabledUser'])->name('enabledUser');
    Route::post('/resendForgottenOtp/{uid}', [OtpPasswordForgottenController::class, 'resendForgottenOtp'])->name('v');
    Route::post('/new_code/{id}', [UserController::class, 'new_code'])->name('new_code');
    Route::post('/verification_code', [UserController::class, 'verification_code'])->name('verification_code');
});



    Route::group(['middleware' => 'auth:api'], function () {

        //Delivery Agency
        Route::prefix('deliveryAgency')->group(function () {
            Route::post('/add', [DeliveryAgencyController::class, 'add'])->name('deliveryAgency.add');
            Route::post('/acceptOrder/{orderUid}', [DeliveryAgencyController::class, 'acceptOrder'])->name('deliveryAgency.acceptOrder');
            Route::get('/getAvailableOrders/{perpage}', [DeliveryAgencyController::class, 'getAvailableOrders'])->name('deliveryAgency.getAvailableOrders');
            Route::post('/becomeDeliveryAgent', [DeliveryAgencyController::class, 'becomeDeliveryAgent'])->name('deliveryAgency.becomeDeliveryAgent');
            Route::get('/getDeliveryAgent', [DeliveryAgencyController::class, 'getDeliveryAgent'])->name('deliveryAgency.getDeliveryAgent');
            Route::get('/getDeliveryAgentConcernedByOrder/{orderUid}', [DeliveryAgencyController::class, 'getDeliveryAgentConcernedByOrder'])->name('deliveryAgency.getDeliveryAgentConcernedByOrder');
            Route::post('/rejectOrder/{orderUid}', [DeliveryAgencyController::class, 'rejectOrder'])->name('deliveryAgency.rejectOrder');
            Route::get('/getAcceptedOrder/{perpage}', [DeliveryAgencyController::class, 'getAcceptedOrder'])->name('deliveryAgency.getAcceptedOrder');
        });

        // Route::post('wallet/generateStandardWallet', [CommissionWalletController::class, 'generateStandardWallet'])->name('wallet.generateStandardWallet');

        //Cart
        Route::prefix('cart')->group(function () {
            Route::post('/actualiseAuthCart', [CartController::class, 'actualiseAuthCart'])->name('cart.actualiseAuthCart');
            Route::post('/addToCart/{ad_id}', [CartController::class, 'addToCart'])->name('cart.addToCart');
            Route::delete('/removeToCart/{adId}', [CartController::class, 'removeToCart'])->name('cart.removeToCart');
            Route::get('/getUserCart/{page}/{perPage}', [CartController::class, 'getUserCart'])->name('cart.getUserCart');
            Route::post('/incrementQuantity', [CartController::class, 'incrementQuantity'])->name('cart.incrementQuantity');
            Route::post('/decrementQuantity', [CartController::class, 'decrementQuantity'])->name('cart.decrementQuantity');
            // Route::get('/getCartItem/{ad_id}', [CartController::class, 'getCartItem'])->name('cart.getCartItem');
        });

        //Commission (Type Wallet)
        Route::prefix('commission')->group(function () {
            Route::put('/restore/{id}', [CommissionController::class, 'restore'])->name('commission.restore');
            Route::delete('/destroy/{id}', [CommissionController::class, 'destroy'])->name('commission.destroy');
            Route::post('/update/{id}', [CommissionController::class, 'update'])->name('commission.update');
            Route::get('/show/{id}', [CommissionController::class, 'show'])->name('commission.show');
            Route::get('/index', [CommissionController::class, 'index'])->name('commission.index');
            Route::post('/store', [CommissionController::class, 'store'])->name('commission.store');
        });

        //Wallet
        Route::prefix('wallet')->group(function () {
            Route::post('/createWallet', [CommissionWalletController::class, 'createWallet'])->name('wallet.createWallet');
            Route::get('/listWallets', [CommissionWalletController::class, 'listWallets'])->name('wallet.listWallets');
            Route::get('/walletDetail/{commissionWalletId}', [CommissionWalletController::class, 'walletDetail'])->name('wallet.walletDetail');
            Route::get('/AuthSTDWalletDetail', [CommissionWalletController::class, 'AuthSTDWalletDetail'])->name('wallet.AuthSTDWalletDetail');
            Route::get('/AuthWallet', [CommissionWalletController::class, 'AuthWallet'])->name('wallet.AuthWallet');
            Route::post('/addFund', [CommissionWalletController::class, 'addFund'])->name('wallet.addFund');
            // Route::post('/generateStandardWallet', [CommissionWalletController::class, 'generateStandardWallet'])->name('wallet.generateStandardWallet');
        });


        //Order
        Route::prefix('order')->group(function () {
            Route::post('/CreateAnOrder', [OrderController::class, 'CreateAnOrder'])->name('order.CreateAnOrder');
            Route::post('/orderSingleItem', [OrderController::class, 'orderSingleItem'])->name('order.orderSingleItem');
            Route::post('/CreateOrderFromProductIds', [OrderController::class, 'CreateOrderFromProductIds'])->name('order.CreateOrderFromProductIds');
            Route::get('/viewOrder/{orderId}', [OrderController::class, 'viewOrder'])->name('order.viewOrder');
            Route::get('/listOrders/{perpage}', [OrderController::class, 'listOrders'])->name('order.listOrders');
            Route::delete('/cancelOrder/{orderId}', [OrderController::class, 'cancelOrder'])->name('order.cancelOrder');
            Route::post('/deleteOrderDetail/{orderDetailId}', [OrderController::class, 'deleteOrderDetail'])->name('order.deleteOrderDetail');
            Route::post('/updateOrderDetail/{orderDetailId}', [OrderController::class, 'updateOrderDetail'])->name('order.updateOrderDetail');
            Route::get('/ordersIndex', [OrderController::class, 'ordersIndex'])->name('order.ordersIndex');
            Route::post('/orderManyItem', [OrderController::class, 'orderManyItem'])->name('order.orderManyItem');
            Route::post('/payOrder/{orderId}', [OrderController::class, 'payOrder'])->name('order.payOrder');
            Route::get('/orderTrade/{orderId}', [OrderController::class, 'orderTrade'])->name('order.orderTrade');
            Route::get('/orderValidatedTrade/{orderId}', [OrderController::class, 'orderValidatedTrade'])->name('order.orderValidatedTrade');
            Route::get('/getOrderEndTrade/{orderId}', [OrderController::class, 'getOrderEndTrade'])->name('order.getOrderEndTrade');
            Route::get('/getOrderCanceledTrade/{orderId}', [OrderController::class, 'getOrderCanceledTrade'])->name('order.getOrderCanceledTrade');
            Route::get('/getAllFinalizedOrders', [OrderController::class, 'getAllFinalizedOrders'])->name('order.getAllFinalizedOrders');
            Route::get('/getOrderAds/{orderUid}', [OrderController::class, 'getOrderAds'])->name('order.getOrderAds');

            Route::get('/getMerchantOrderAds/{orderUid}', [OrderController::class, 'getMerchantOrderAds'])->name('order.getMerchantOrderAds');
            
            Route::get('/userOrders/{perpage}', [OrderController::class, 'userOrders'])->name('order.userOrders');
            Route::get('/getMerchantOrder', [OrderController::class, 'getMerchantOrder'])->name('order.getMerchantOrder');
            Route::get('/getMerchantOrderWithDelivery', [OrderController::class, 'getMerchantOrderWithDelivery'])->name('order.getMerchantOrderWithDelivery');
            Route::post('/CreateAndPayOrder', [OrderController::class, 'CreateAndPayOrder'])->name('order.CreateAndPayOrder');
        });


        //user
        Route::get('/user', [UserController::class, 'userAuth']);
        Route::prefix('users')->group(function () {
            Route::post('/new_code/{id}', [UserController::class, 'new_code'])->name('new_code');
        });

           //person

           Route::prefix('person')->group(function () {
               Route::post('/updatePersonInformation', [PersonController::class, 'updatePersonInformation'])->name('person.updatePersonInformation');
               Route::post('/AddOrUpdateProfileImg', [PersonController::class, 'AddOrUpdateProfileImg'])->name('person.AddOrUpdateProfileImg');
           });

        //Ad
        Route::prefix('ad')->group(function () {
           
            Route::get('/marchand/{perpage}', [AdController::class, 'getMarchandAd'])->name('ad.marchand');
            Route::delete('/destroyAd/{uid}', [AdController::class, 'destroyAd'])->name('ad.destroyAdd');
        });

          //Notification
          Route::prefix('notification')->group(function () {
            Route::get('/getNotifications/{perpage}', [NotificationController::class, 'getNotifications'])->name('notification.getNotifications');
            Route::post('/create', [NotificationController::class, 'create'])->name('notification.create');
            Route::post('/makeAsReadOrUnRead', [NotificationController::class, 'makeAsReadOrUnRead'])->name('notification.makeAsReadOrUnRead');
            Route::post('/createGeneralNotification', [NotificationController::class, 'createGeneralNotification'])->name('notification.createGeneralNotification');
            Route::get('/deleteNotification/{perpage}', [NotificationController::class, 'deleteNotification'])->name('notification.deleteNotification');
        });

        //Preorder
        Route::prefix('preorder')->group(function () {
            Route::post('/createPreorder', [PreorderController::class, 'createPreorder'])->name('preorder.createPreorder');
            Route::post('/validatePreorder/{uid}', [PreorderController::class, 'validatePreorder'])->name('preorder.validatePreorder');
            Route::post('/rejectPreorder/{uid}', [PreorderController::class, 'rejectPreorder'])->name('preorder.rejectPreorder');
            Route::get('/merchantAffectedByPreorder/{perPage}', [PreorderController::class, 'merchantAffectedByPreorder'])->name('preorder.merchantAffectedByPreorder');
            Route::get('/getValidatedPreordersWithAnswers/{preorderUid}/{perPage}', [PreorderController::class, 'getValidatedPreordersWithAnswers'])->name('preorder.getValidatedPreordersWithAnswers');
            Route::get('/getPreordersAnswerSortedByPrice/{preorderUid}/{perPage}', [PreorderController::class, 'getPreordersAnswerSortedByPrice'])->name('preorder.getPreordersAnswerSortedByPrice');
            Route::get('/getPreordersAnswerSortedByDeliveryTime/{preorderUid}/{perPage}', [PreorderController::class, 'getPreordersAnswerSortedByDeliveryTime'])->name('preorder.getPreordersAnswerSortedByDeliveryTime');
        
        });

        //PreOrderAnswer
        Route::prefix('preorder_answer')->group(function () {
            Route::post('/createPreorderAnswer/{preorderId}', [PreorderController::class, 'createPreorderAnswer'])->name('preorder.createPreorderAnswer');
            Route::post('/validatePreorderAnswer/{uid}', [PreorderController::class, 'validatePreorderAnswer'])->name('preorder.validatePreorderAnswer');
            Route::post('/rejectPreorderAnswer/{uid}', [PreorderController::class, 'rejectPreorderAnswer'])->name('preorder.rejectPreorderAnswer');
        });

        //Review
        Route::prefix('review')->group(function () {
            Route::post('/write/{PreordersAnswerUid}', [PreorderController::class, 'write'])->name('review.write');
            Route::get('/answerReviews/{PreordersAnswerUid}', [PreorderController::class, 'answerReviews'])->name('preorder.answerReviews');
            Route::get('/answerReviewsPaginate/{PreordersAnswerUid}/{perpage}', [PreorderController::class, 'answerReviewsPaginate'])->name('preorder.answerReviewsPaginate');
          
        });

        // Trade
        Route::prefix('trade')->group(function () {
            Route::post('/createTrade', [TradeController::class, 'createTrade'])->name('trade.createTrade');
            Route::post('/updateTradeStatusCompleted/{tradeId}', [TradeController::class, 'updateTradeStatusCompleted'])->name('trade.updateTradeStatusCompleted');
            Route::post('/updateTradeStatusCanceled/{tradeId}', [TradeController::class, 'updateTradeStatusCanceled'])->name('trade.updateTradeStatusCanceled');
            Route::post('/updateTradeStatusDisputed/{tradeId}', [TradeController::class, 'updateTradeStatusDisputed'])->name('trade.updateTradeStatusDisputed');
            Route::get('/displayTrades', [TradeController::class, 'displayTrades'])->name('trade.displayTrades');
            Route::get('/displayTradesWithoutEndDate', [TradeController::class, 'displayTradesWithoutEndDate'])->name('trade.displayTradesWithoutEndDate');
            Route::post('/updateEndDate/{tradeId}', [TradeController::class, 'updateEndDate'])->name('trade.updateEndDate');
            Route::get('/getTradeStageDone/{tradeId}', [TradeController::class, 'getTradeStageDone'])->name('tradeStage.getTradeStageDone');
            Route::get('/getTradeStageNotDoneYet/{tradeId}', [TradeController::class, 'getTradeStageNotDoneYet'])->name('tradeStage.getTradeStageNotDoneYet');
            Route::get('/getTradeStage/{tradeId}', [TradeController::class, 'getTradeStage'])->name('tradeStage.getTradeStage');
            Route::get('/getAuthTradeStage/{tradeId}', [TradeController::class, 'getAuthTradeStage'])->name('tradeStage.getAuthTradeStage');
            Route::get('/getTradeChat/{tradeId}', [TradeController::class, 'getTradeChat'])->name('tradeStage.getTradeChat');
            Route::post('/validateTrade/{tradeId}', [TradeController::class, 'validateTrade'])->name('trade.validateTrade');
            Route::get('/getAllTrades/{perpage}', [TradeController::class, 'getAllTrades'])->name('trade.getAllTrades');
            Route::get('/getUnvalidatedTrades/{perpage}', [TradeController::class, 'getUnvalidatedTrades'])->name('trade.getUnvalidatedTrades');
        });

        //OngingTradeStage
        Route::prefix('ongingtradeStage')->group(function () {
            Route::post('/makeCompleteTradeStage/{ongingTradeStageId}', [OngingTradeStageController::class, 'makeCompleteTradeStage'])->name('ongingtradeStage.makeCompleteTradeStage');
            Route::post('/makeInCompleteTradeStage/{ongingTradeStageId}', [OngingTradeStageController::class, 'makeInCompleteTradeStage'])->name('ongingtradeStage.makeInCompleteTradeStage');
            Route::post('/yes_action/{ongingTradeStageId}', [OngingTradeStageController::class, 'yes_action'])->name('ongingtradeStage.yes_action');
            Route::get('/showTradeStage/{ongingTradeStageId}', [OngingTradeStageController::class, 'showTradeStage'])->name('ongingtradeStage.showTradeStage');
            Route::post('/no_action/{ongingTradeStageId}', [OngingTradeStageController::class, 'no_action'])->name('ongingtradeStage.no_action');
            Route::post('/updateOngingTradeStage/{ongingTradeStageId}', [OngingTradeStageController::class, 'updateOngingTradeStage'])->name('ongingtradeStage.updateOngingTradeStage');
            Route::post('/handleTradeStageAction/{ongingtradeStageId}/{actionType}', [OngingTradeStageController::class, 'handleTradeStageAction'])->name('order.handleTradeStageAction');
        });


        //TradeChat
        Route::prefix('tradeChat')->group(function () {
            Route::post('/createTradeChat/{tradeId}', [TradeChatController::class, 'createTradeChat'])->name('tradeChat.createTradeChat');
            Route::post('/markTradeChatAsSpam/{tradeChatId}', [TradeChatController::class, 'markTradeChatAsSpam'])->name('tradeChat.markTradeChatAsSpam');
            Route::post('/archiveTradeChat/{tradeChatId}', [TradeChatController::class, 'archiveTradeChat'])->name('tradeChat.archiveTradeChat');
            Route::get('/getMessageOfTradeChat/{tradeChatId}', [TradeChatController::class, 'getMessageOfTradeChat'])->name('tradeChat.getMessageOfTradeChat');
        });

        // ChatMessage
        Route::prefix('chatMessage')->group(function () {
            Route::post('/createChatMessage/{tradeId}', [ChatMessageController::class, 'createChatMessage'])->name('chatMessage.createTradeChat');
            Route::post('/markMessageAsRead/{chatMessageId}', [ChatMessageController::class, 'markMessageAsRead'])->name('chatMessage.markMessageAsRead');
            Route::post('/markMessageAsUnRead/{chatMessageId}', [ChatMessageController::class, 'markMessageAsUnRead'])->name('chatMessage.markMessageAsUnRead');
        });
        // Route::get('/ad/all', [AdController::class, 'getAllAd'])->name('ad.all');

           //Category
          
        

            //Favorite
            Route::prefix('favorite')->group(function () {
                Route::post('/addAdToFavorite/{adId}', [FavoriteController::class, 'addAdToFavorite'])->name('favorite.addAdToFavorite');
                Route::get('/GetFavoritesAd/{page}/{perPage}', [FavoriteController::class, 'GetFavoritesAd'])->name('favorite.GetFavoritesAd');
                Route::delete('/RemoveAdFromFavoriteList/{id}', [FavoriteController::class, 'RemoveAdFromFavoriteList'])->name('favorite.RemoveAdFromFavoriteList');
                Route::get('/all', [FavoriteController::class, 'all'])->name('favorite.all');
            });

              //type of type
            Route::prefix('typeoftype')->group(function () {
                Route::post('/store', [TypeOfTypeController::class, 'store']);
                Route::get('/show/{id}', [TypeOfTypeController::class, 'show']);
                Route::post('/update/{id}', [TypeOfTypeController::class, 'update']);
                Route::post('/delete/{id}', [TypeOfTypeController::class, 'delete']);
            });

            //attribute group
            Route::prefix('attributeGroup')->group(function () {
                Route::post('/store', [AttributeGroupController::class, 'store']);
            });
        
            //escrow
            Route::prefix('escrow')->group(function () {
                Route::get('/getEscrow/{perpage}', [EscrowController::class, 'getEscrow']);
                Route::get('/showEscrow/{id}', [EscrowController::class, 'showEscrow']);
            });

            //Category Attribute
            Route::prefix('categoryAttribute')->group(function () {
                Route::post('/store', [CategoryAttributesController::class, 'store']);
            });

            //File
            Route::prefix('file')->group(function () {
                Route::get('/getFilesByFileCode/{file}/{returnSingleFile}', [FileController::class, 'getFilesByFileCode']);
                Route::delete('/removeFile/{uid}', [Service::class, 'removeFile'])->name("file.removeFile");
            });


            //Preorder
            Route::prefix('preorder')->group(function () {
                Route::get('/getPreorderValidated/{perpage}', [PreorderController::class, 'getPreorderValidated'])->name('preorder.getPreorderValidated');
                Route::get('/getPreorderPending', [PreorderController::class, 'getPreorderPending'])->name('preorder.getPreorderPending');
                Route::get('/getPreorderWitnAnswer', [PreorderController::class, 'getPreorderWitnAnswer'])->name('preorder.getPreorderWitnAnswer');
                Route::get('/getPreorderRejected', [PreorderController::class, 'getPreorderRejected'])->name('preorder.getPreorderRejected');
                Route::get('/getPreorderWithValidatedAnswers/{uid}/{perpage}', [PreorderController::class, 'getPreorderWithValidatedAnswers'])->name('preorder.getPreorderWithValidatedAnswers');
                Route::get('/getAuthPreorderValidated/{perpage}', [PreorderController::class, 'getAuthPreorderValidated'])->name('preorder.getAuthPreorderValidated');
            });

            //Preorder Answer
            Route::prefix('preorder_answer')->group(function () {
                Route::get('/getPreorderAnswerValidated/{perpage}', [PreorderController::class, 'getPreorderAnswerValidated'])->name('preorder.getPreorderAnswerValidated');
                Route::get('/getPreorderAnswerPending', [PreorderController::class, 'getPreorderAnswerPending'])->name('preorder.getPreorderAnswerPending');
                Route::get('/getPreorderAnswerRejected', [PreorderController::class, 'getPreorderAnswerRejected'])->name('preorder.getPreorderAnswerRejected');
                Route::get('/getSpecificPreorderAnswer/{uid}', [PreorderController::class, 'getSpecificPreorderAnswer'])->name('preorder.getSpecificPreorderAnswer');
            });

            //Shop
            Route::prefix('shop')->group(function () {
                Route::post('/updateShop/{uid}', [ShopController::class, 'updateShop'])->name('shop.updateShop');
                Route::get('/showShop/{uid}', [ShopController::class, 'showShop'])->name('shop.showShop');
                Route::post('/addShopFile/{filecodeShop}', [ShopController::class, 'addShopFile'])->name('preorder.addShopFile');
                Route::post('/updateShopFile/{uid}', [ShopController::class, 'updateShopFile'])->name('shop.updateShopFile');
                Route::post('/addCategoryToSHop/{shopId}', [ShopController::class, 'addCategoryToSHop'])->name('shop.addCategoryToSHop');
                Route::post('/RemoveCategoryToSHop/{shopId}', [ShopController::class, 'RemoveCategoryToSHop'])->name('shop.RemoveCategoryToSHop');
                Route::post('/becomeMerchant', [ShopController::class, 'becomeMerchant'])->name('shop.becomeMerchant');
                Route::get('/AdMerchant/{shopId}', [ShopController::class, 'AdMerchant'])->name('shop.AdMerchant');
                Route::get('/userShop', [ShopController::class, 'userShop'])->name('shop.userShop');
                Route::get('/getOrdersShop/{shopUid}', [ShopController::class, 'getOrdersShop'])->name('shop.getOrdersShop');
                Route::get('/userPaginateShop/{perpage}', [ShopController::class, 'userPaginateShop'])->name('shop.userPaginateShop');
                Route::get('categories/{shopId}', [ShopController::class, 'getShopCategorie'])->name('shop.getShopCategorie');
                Route::post('/createShop', [ShopController::class, 'createShop'])->name('shop.createShop');
                Route::get('/getShopOrderAds/{orderUid}/{shopUid}', [ShopController::class, 'getShopOrderAds'])->name('order.getShopOrderAds');
                Route::get('/getMerchantStatistic', [ShopController::class, 'getMerchantStatistic'])->name('shop.getMerchantStatistic');
                });

                //TradeStage
                Route::prefix('tradeStage')->group(function () {
                    Route::post('/createTradeStage', [TradeStageController::class, 'createTradeStage'])->name('tradeStage.createTradeStage');
                    Route::post('/updateTradeStage/{tradeStageId}', [TradeStageController::class, 'updateTradeStage'])->name('tradeStage.updateTradeStage');
                    Route::get('/displayTradeStages/{tradeId}', [TradeStageController::class, 'displayTradeStages'])->name('tradeStageId.displayTradeStages');
                    Route::get('/index', [TradeStageController::class, 'index'])->name('tradeStageId.index');
                    Route::post('/delete/{tradeStageId}', [TradeStageController::class, 'delete'])->name('tradeStage.delete');
                });
            
                //Merchant
                Route::prefix('merchant')->group(function () {
                    Route::get('/getMerchant', [ClientController::class, 'getMerchant'])->name('merchant.getMerchant');
                });

                //Transaction
                Route::prefix('transaction')->group(function () {
                    Route::get('/getUserTransactions', [TransactionController::class, 'getUserTransactions'])->name('transaction.getUserTransactions');
                });

                 //Zone
                 Route::prefix('zone')->group(function () {
                    Route::get('/index/{paginate}', [ZoneController::class, 'index'])->name('zone.index');
                    Route::post('/store', [ZoneController::class, 'store'])->name('zone.store');
                    Route::post('/destroy/{uid}', [ZoneController::class, 'destroy'])->name('zone.destroy');
                    Route::post('/makeZoneActiveOrNot/{uid}', [ZoneController::class, 'makeZoneActiveOrNot'])->name('zone.makeZoneActiveOrNot');
                    Route::post('/update/{uid}', [ZoneController::class, 'update'])->name('zone.update');
                });

                  //deliveryzone
                  Route::prefix('deliveryzone')->group(function () {
                    Route::post('/addZone/{zoneUid}', [DeliveryAgentZoneController::class, 'addZone'])->name('deliveryzone.addZone');
                    Route::post('/removeZone/{zoneUid}', [DeliveryAgentZoneController::class, 'removeZone'])->name('deliveryzone.removeZone');
                    Route::get('/DeliveryAgentZones', [DeliveryAgentZoneController::class, 'DeliveryAgentZones'])->name('deliveryzone.DeliveryAgentZones');
                });

                //Address

                Route::prefix('address')->group(function () {
                    Route::post('/createAddress', [AddressController::class, 'createAddress'])->name('address.createAddress');
                    Route::get('/getAddress/{addressUid}', [AddressController::class, 'getAddress'])->name('address.getAddress');
                    Route::get('/getAllAuthAddresses', [AddressController::class, 'getAllAuthAddresses'])->name('address.getAllAuthAddresses');
                    Route::get('/getAllUserAddresses/{userUid}', [AddressController::class, 'getAllUserAddresses'])->name('address.getAllUserAddresses');
                    Route::post('/updateAddress/{addressUid}', [AddressController::class, 'updateAddress'])->name('address.updateAddress');
                    Route::post('/deleteAddress/{id}', [AddressController::class, 'deleteAddress'])->name('address.deleteAddress');
                    Route::get('/getActiveService', [AddressController::class, 'getActiveService'])->name('address.getActiveService');
                    Route::get('/getUserActiveService/{userUid}', [AddressController::class, 'getUserActiveService'])->name('address.getUserActiveService');
                });
                

    });

    

 

   
    //Ad
    Route::prefix('ad')->group(function () {
        Route::get('/all/{perPage}', [AdController::class, 'getRecentAdd'])->name('ad.recent');
        Route::get('/all', [AdController::class, 'getAllAd'])->name('ad.all');
        Route::post('/storeAd', [AdController::class, 'storeAd'])->name('ad.storeAd');
        Route::post('/editAd/{uid}', [AdController::class, 'editAd'])->name('ad.editAd');
        Route::post('/uploadAdImage/{adUid}', [Service::class, 'uploadAdImage'])->name('ad.uploadAdImage');
        Route::post('/validateAd/{uid}', [AdController::class, 'validateAd'])->name('ad.validateAd');
        Route::post('/rejectAd/{uid}', [AdController::class, 'rejectAd'])->name('ad.rejectAd');
        Route::get('/checkIfAdIsPending/{uid}', [AdController::class, 'checkIfAdIsPending'])->name('ad.checkIfAdIsPending');
        Route::get('/getAdDetail/{uid}', [AdController::class, 'getAdDetail'])->name('ad.getAdDetail');
        Route::get('/checkIfAdIsRejected/{uid}', [AdController::class, 'checkIfAdIsRejected'])->name('ad.checkIfAdIsRejected');
        Route::get('/checkIfAdIsValidated/{uid}', [AdController::class, 'checkIfAdIsValidated'])->name('ad.checkIfAdIsValidated');
        Route::get('/getAdBySubCategory/{catagoryUid}', [AdController::class, 'getAdBySubCategory'])->name('ad.getAdBySubCategory');
        Route::post('/addInventoryToAd/{adUid}', [AdController::class, 'addInventoryToAd'])->name('ad.addInventoryToAd');
        Route::get('/ads', [AdController::class, 'allAds'])->name('ad.allAds');
        Route::post('/editAd/{uid}', [AdController::class, 'editAd'])->name('ad.editAd');
    });

   
    

    // Route::prefix('right')->group(function () {
    //     Route::get('/index', [RightController::class, 'index']);
    //     Route::post('/store', [RightController::class, 'store']);
    //     Route::get('/show/{id}', [RightController::class, 'show']);
    //     Route::put('/update/{id}', [RightController::class, 'update']);
    //     Route::delete('/destroy/{id}', [RightController::class, 'destroy']);
    // });

  

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







// other


    Route::post('/refundDeliveryAgent/{id}', [OngingTradeStageService::class, 'refundDeliveryAgent'])->name('tradeStage.refundDeliveryAgent');

    Route::post('/fundDeliveryAgent/{id}', [OngingTradeStageService::class, 'fundDeliveryAgent'])->name('tradeStage.fundDeliveryAgent');

    Route::get('/returnClientIdAuth', [Service::class, 'returnClientIdAuth'])->name('service.returnClientIdAuth');

    Route::get('/order/ordersIndex', [OrderController::class, 'ordersIndex'])->name('order.ordersIndex');

    Route::get('/getMerchantCOncernedByPreorder/{preorderUid}', [ClientController::class, 'getMerchantCOncernedByPreorder'])->name('order.getMerchantCOncernedByPreorder');



    Route::prefix('admin')->group(function () {
        Route::post('/create/{id}', [AdminController::class, 'create'])->name('admin.create');
        Route::post('/delete/{id}', [AdminController::class, 'delete'])->name('admin.delete');
        Route::get('/adminUserAccount', [Service::class, 'adminUserAccount'])->name('admin.adminUserAccount');
    });


    Route::get('isWithinDeliveryZone/{longitude}/{latitude}', [ZoneController::class, 'isWithinDeliveryZone'])->name('deliveryAgency.isWithinDeliveryZone');

    Route::get('isWithinDeliveryZoneO/{longitude}/{latitude}', [ZoneController::class, 'isWithinDeliveryZoneO'])->name('deliveryAgency.isWithinDeliveryZoneO');

    Route::post('/notifyParty/{orderId}/{longitue}/{latitude}', [OrderController::class, 'notifyParty'])->name('order.notifyParty');  Route::prefix('category')->group(function () {
        Route::post('/add', [CategoryController::class, 'add'])->name('category.add');
        Route::post('/updateCategorie/{id}', [CategoryController::class, 'updateCategorie'])->name('category.updateCategorie');
        Route::get('/detail/{uid}', [CategoryController::class, 'showCategoryDetail'])->name('category.detail');
        Route::get('/all', [CategoryController::class, 'getAllCategories'])->name('category.all');
        Route::get('/search', [CategoryController::class, 'searchCategory'])->name('category.search');
        Route::get('/all/{paginate}', [CategoryController::class, 'getAllPaginateCategories'])->name('category.getAllPaginateCategories');
        Route::get('/getAllPaginateSubSubcategory/{paginate}', [CategoryController::class, 'getAllPaginateSubSubcategory'])->name('category.getAllPaginateSubSubcategory');
        Route::get('/getAllSubSubcategory', [CategoryController::class, 'getAllSubSubcategory'])->name('category.getAllSubSubcategory');
    });

        //Country
        Route::prefix('country')->group(function () {
            Route::post('/load', [CountryController::class, 'load'])->name('country.load');
            Route::post('/add', [CountryController::class, 'add'])->name('country.add');
            Route::get('/all', [CountryController::class, 'getAllCountries'])->name('country.all');
            Route::get('/all/{perpage}', [CountryController::class, 'getAllPaginateCountries'])->name('country.getAllPaginateCountries');
        });


    Route::get('/test/create', [AdDetailController::class, 'create'])->name('create.create');

    Route::post('/file/storeSingleFile/{random}/{location}', [Service::class, 'storeSingleFile'])->name('create.storeSingleFile');

    Route::post('/file/storeFileNative', [Service::class, 'storeFileNative'])->name('create.storeFileNative');


    Route::post('/file/storeImage', [Service::class, 'storeImage'])->name('create.storeImage');