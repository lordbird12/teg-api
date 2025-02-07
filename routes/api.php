<?php

use App\Http\Controllers\CategoryProductController;
use App\Http\Controllers\CategoryNewsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PriceRateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\AppManagementController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\AppConfigController;
use App\Http\Controllers\QuestionMasterController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransportThaiMasterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RegisterImporterController;
use App\Http\Controllers\WalletTransactionController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\CategoryMemberManualController;
use App\Http\Controllers\MemberManualController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\AddOnServiceController;
use App\Http\Controllers\StandardSizeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

//////////////////////////////////////////web no route group/////////////////////////////////////////////////////
//Login Admin
Route::post('/login', [LoginController::class, 'login']);
Route::post('/login_app', [LoginController::class, 'loginMember']);

Route::post('/check_login', [LoginController::class, 'checkLogin']);

//user
Route::post('/create_admin', [UserController::class, 'createUserAdmin']);
Route::post('/forgot_password_user', [UserController::class, 'ForgotPasswordUser']);

// Category Product
Route::resource('category_product', CategoryProductController::class);
Route::post('/category_product_page', [CategoryProductController::class, 'getPage']);
Route::get('/get_category_product', [CategoryProductController::class, 'getList']);
Route::post('/update_category_product', [CategoryProductController::class, 'updateData']);
Route::get('/get_category_product_lat_lon/{lat}/{lon}', [CategoryProductController::class, 'getListLatLon']);

// Product
Route::resource('product', ProductController::class);
Route::post('/product_page', [ProductController::class, 'getPage']);
Route::get('/get_product/{id}', [ProductController::class, 'getList']);
Route::post('/update_product', [ProductController::class, 'updateData']);
Route::get('/get_product_all', [ProductController::class, 'getListAll']);

// Department
Route::resource('department', DepartmentController::class);
Route::post('/department_page', [DepartmentController::class, 'getPage']);
Route::get('/get_department', [DepartmentController::class, 'getList']);

// Postion
Route::resource('position', PositionController::class);
Route::post('/position_page', [PositionController::class, 'getPage']);
Route::get('/get_position', [PositionController::class, 'getList']);

// Category News
Route::resource('category_news', CategoryNewsController::class);
Route::post('/category_news_page', [CategoryNewsController::class, 'getPage']);
Route::get('/get_category_news', [CategoryNewsController::class, 'getList']);
Route::post('/update_category_news', [CategoryNewsController::class, 'updateData']);

// News
Route::resource('news', NewsController::class);
Route::post('/news_page', [NewsController::class, 'getPage']);
Route::get('/get_news', [NewsController::class, 'getList']);
Route::post('/update_news', [NewsController::class, 'updateData']);

// Faq
Route::resource('faq', FaqController::class);
Route::post('/faq_page', [FaqController::class, 'getPage']);
Route::get('/get_faq', [FaqController::class, 'getList']);

// Rate
Route::resource('rate', PriceRateController::class);
Route::post('/rate_page', [PriceRateController::class, 'getPage']);
Route::get('/get_rate/{vehicle}', [PriceRateController::class, 'getList']);

// Member
Route::resource('member', MemberController::class);
Route::post('/member_page', [MemberController::class, 'getPage']);
Route::get('/get_member', [MemberController::class, 'getList']);
Route::post('/update_member', [MemberController::class, 'updateData']);
Route::post('open_shop', [MemberController::class, 'openShop']);

// Store
Route::resource('store',StoreController::class);
Route::post('/store_page', [StoreController::class, 'getPage']);
Route::get('/get_store', [StoreController::class, 'getList']);
Route::post('/update_store', [StoreController::class, 'updateData']);

// Question Master
Route::resource('question_master',QuestionMasterController::class);
Route::post('/question_master_page', [QuestionMasterController::class, 'getPage']);
Route::get('/get_question_master/{type}', [QuestionMasterController::class, 'getList']);

// Service
Route::resource('services',ServiceController::class);
Route::post('/services_page', [ServiceController::class, 'getPage']);
Route::get('/get_services', [ServiceController::class, 'getList']);
Route::post('/update_services', [ServiceController::class, 'updateData']);

// Transport
Route::resource('transport',TransportThaiMasterController::class);
Route::post('/transport_page', [TransportThaiMasterController::class, 'getPage']);
Route::get('/get_transport', [TransportThaiMasterController::class, 'getList']);
Route::post('/update_transport', [TransportThaiMasterController::class, 'updateData']);

// App Management
Route::resource('app_manage', AppManagementController::class);
Route::post('/app_manage_page', [AppManagementController::class, 'getPage']);
Route::get('/get_app_manage', [AppManagementController::class, 'getListAll']);

// App Config
Route::resource('app_config', AppConfigController::class);
Route::post('/app_config_page', [AppConfigController::class, 'getPage']);
Route::get('/get_app_config', [AppConfigController::class, 'getList']);

// Permission
Route::resource('permission', PermissionController::class);
Route::post('/permission_page', [PermissionController::class, 'getPage']);
Route::get('/get_permission', [PermissionController::class, 'getList']);
Route::post('/get_permisson_menu', [PermissionController::class, 'getPermissonMenu']);

// Location
Route::resource('rate', PriceRateController::class);
Route::post('/rate_page', [PriceRateController::class, 'getPage']);
Route::get('/get_rate/{vehicle}', [PriceRateController::class, 'getList']);

//controller
Route::post('upload_images', [Controller::class, 'uploadImages']);
// Route::post('upload_file', [Controller::class, 'uploadFile']);

//user
Route::get('/get_user', [UserController::class, 'getList']);
Route::post('/user_page', [UserController::class, 'getPage']);
Route::get('/user_profile', [UserController::class, 'getProfileUser']);

Route::get('/get_user_by_department/{id}', [UserController::class, 'getUserByDep']);
Route::put('/reset_password_user/{id}', [UserController::class, 'ResetPasswordUser']);
Route::post('/update_profile_user', [UserController::class, 'updateProfileUser']);
Route::get('/get_profile_user', [UserController::class, 'getProfileUser']);

// Order
Route::resource('orders', OrderController::class);
Route::post('/orders_page', [OrderController::class, 'getPage']);
Route::get('/get_orders', [OrderController::class, 'getList']);
Route::post('/update_status_order', [OrderController::class, 'updateStatus']);
Route::get('/get_orders_by_member/{id}', [OrderController::class, 'getListByStatus']);

// Delivery Order
Route::resource('delivery_orders', DeliveryOrderController::class);
Route::post('/delivery_orders_page', [DeliveryOrderController::class, 'getPage']);
Route::get('/get_delivery_orders', [DeliveryOrderController::class, 'getList']);
Route::post('/update_status_delivery_orders', [DeliveryOrderController::class, 'updateStatus']);
Route::get('/get_delivery_orders_by_member/{id}', [DeliveryOrderController::class, 'getListByStatus']);

// Register Importer
Route::resource('register_importer', RegisterImporterController::class);
Route::post('/register_importer_page', [RegisterImporterController::class, 'getPage']);
Route::get('/get_register_importer', [RegisterImporterController::class, 'getList']);

// Category Manual
Route::resource('category_manual', CategoryMemberManualController::class);
Route::post('/category_manual_page', [CategoryMemberManualController::class, 'getPage']);
Route::get('/get_category_manual', [CategoryMemberManualController::class, 'getList']);
Route::post('/update_category_manual', [CategoryMemberManualController::class, 'updateData']);

// Manual
Route::resource('manual', MemberManualController::class);
Route::post('/manual_page', [MemberManualController::class, 'getPage']);
Route::get('/get_manual', [MemberManualController::class, 'getList']);
Route::post('/update_manual', [MemberManualController::class, 'updateData']);

// Manual
Route::resource('add_on_services', AddOnServiceController::class);
Route::post('/add_on_services_page', [AddOnServiceController::class, 'getPage']);
Route::get('/get_add_on_services', [AddOnServiceController::class, 'getList']);
Route::post('/update_add_on_services', [AddOnServiceController::class, 'updateData']);

// Order Payment
Route::resource('payment_order', OrderPaymentController::class);
Route::post('/payment_order_page', [OrderPaymentController::class, 'getPage']);
Route::get('/get_payment_order', [OrderPaymentController::class, 'getList']);
Route::post('/update_payment_order', [OrderPaymentController::class, 'updateData']);

// Standard Size
Route::resource('standard_size', StandardSizeController::class);
Route::post('/standard_size_page', [StandardSizeController::class, 'getPage']);
Route::get('/get_standard_size', [StandardSizeController::class, 'getList']);

Route::put('/update_password_user/{id}', [UserController::class, 'updatePasswordUser']);
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////

Route::group(['middleware' => 'checkjwt'], function () {
    Route::resource('user', UserController::class);
    Route::post('/user_delete_all', [UserController::class, 'destroy_all']);
    Route::post('/update_user', [UserController::class, 'update']);


    // Wallet transaction
    Route::resource('wallet_trans', WalletTransactionController::class);
    Route::post('/wallet_trans_page', [WalletTransactionController::class, 'getPage']);
    Route::get('/get_wallet_trans/{id}', [WalletTransactionController::class, 'getList']);
});


//upload
Route::post('/upload_file', [UploadController::class, 'uploadFile']);

