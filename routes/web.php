<?php

use App\Http\Livewire\Change;
use App\Http\Livewire\Charge;
use App\Http\Livewire\Customer;
use App\Http\Livewire\Error404;
use App\Http\Livewire\Error500;
use App\Http\Livewire\Error501;
use App\Http\Livewire\FortnightReturn;
use App\Http\Livewire\Grade;
use App\Http\Livewire\IMEI;
use App\Http\Livewire\Index;
use App\Http\Livewire\Profile;
use App\Http\Livewire\Signin;
use App\Http\Livewire\Order;
use App\Http\Livewire\Wholesale;
use App\Http\Livewire\Inventory;
use App\Http\Livewire\Issue;
use App\Http\Livewire\Listing;
use App\Http\Livewire\Product;
use App\Http\Livewire\Variation;
use App\Http\Livewire\Process;
use App\Http\Livewire\Logout;
use App\Http\Livewire\MoveInventory;
use App\Http\Livewire\Repair;
use App\Http\Livewire\Report;
use App\Http\Livewire\RMA;
use App\Http\Livewire\SalesReturn;
use App\Http\Livewire\Team;
use App\Http\Livewire\Testing;
use App\Http\Controllers\ExchangeRateController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GoogleController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ReportController;
use App\Http\Livewire\Stock_room;
use App\Http\Livewire\Wholesale_return;

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

// Route::get('/', function () {
//     return view('livewire.index');
// });
Route::get('/', Index::class)->name('index');
Route::get('index', Index::class)->name('index');
Route::get('index/toggle_amount_view', [Index::class,'toggle_amount_view'])->name('index');
Route::get('index/add_ip', [Index::class,'add_ip'])->name('add_ip');
Route::get('index/stock_cost_summery', [Index::class,'stock_cost_summery'])->name('available_stock_cost_summery');
Route::get('index/test', [Index::class,'test'])->name('test');
Route::get('index/refresh_sales_chart', [Index::class,'refresh_sales_chart'])->name('10_day_sales_chart');
Route::get('index/refresh_7_days_chart', [Index::class,'refresh_7_days_chart'])->name('10_day_sales_chart');
Route::get('index/refresh_7_days_progressive_chart', [Index::class,'refresh_7_days_progressive_chart'])->name('7_day_progressive_sales_chart');
Route::get('index/required_restock', [Index::class,'required_restock'])->name('required_restock');
// Route::post('change', Change::class);
Route::get('error404', Error404::class)->name('error');
Route::get('error500', Error500::class)->name('error');
Route::get('error501', Error501::class)->name('error');
Route::get('profile', Profile::class)->name('profile');
Route::post('profile', Profile::class)->name('profile');
Route::get('signin', Signin::class)->name('login');
Route::post('login', [Signin::class,'login'])->name('signin');
Route::get('logout', Logout::class)->name('signin');

// Route::middleware('auth')->group(function () {
//     Route::get('/2fa', [TwoFactorController::class, 'show2faForm'])->name('2fa.form');
//     Route::post('/2fa/setup', [TwoFactorController::class, 'setup2fa'])->name('2fa.setup');
//     Route::post('/2fa', [TwoFactorController::class, 'verify2fa'])->name('2fa.verify');
// });
// Route::get('/2fa', 'TwoFactorController@show')->name('2fa');
// Route::post('/2fa', 'TwoFactorController@verify')->name('2fa.verify');

// Route::middleware(['auth', '2fa'])->group(function () {

Route::get('purchase', [Order::class,'purchase'])->name('view_purchase');
Route::post('add_purchase', [Order::class,'add_purchase'])->name('add_purchase');
Route::post('add_purchase_item/{id}', [Order::class,'add_purchase_item'])->name('add_purchase_item');
Route::get('delete_order/{id}', [Order::class,'delete_order'])->name('delete_purchase');
Route::get('delete_order_item/{id}', [Order::class,'delete_order_item'])->name('delete_purchase_item');
Route::get('purchase/detail/{id}', [Order::class,'purchase_detail'])->name('purchase_detail');
Route::get('purchase/purchase_model_graded_count/{order_id}/{pss_id}', [Order::class,'purchase_model_graded_count'])->name('purchase_detail');
Route::post('purchase/approve/{id}', [Order::class,'purchase_approve'])->name('purchase_approve');
Route::get('purchase/revert_status/{id}', [Order::class,'purchase_revert_status'])->name('purchase_revert_status');
Route::post('purchase/remove_issues', [Order::class,'remove_issues'])->name('remove_purchase_issues');

Route::get('report_new', [ReportController::class, 'index'])->name('view_report');

Route::get('report', Report::class)->name('view_report');
Route::get('report/projected_sales', [Report::class,'projected_sales'])->name('view_report');
Route::get('report/pass', [Report::class,'pass'])->name('view_report');
Route::post('report/check_password', [Report::class,'check_password'])->name('view_report');
Route::post('report/set_password', [Report::class,'set_password'])->name('view_report');
Route::get('report/export', [Report::class,'export_report'])->name('view_report');
Route::get('report/export_batch/{orderId}', [Report::class,'export_batch_report'])->name('view_report');
Route::post('report/stock_report', [Report::class,'stock_report'])->name('stock_report');
Route::get('report/ecommerce_report', [Report::class,'ecommerce_report'])->name('ecommerce_report');
Route::post('report/pnl', [Report::class,'pnl'])->name('pnl');
Route::get('report/vendor_report/{vendor_id}', [Report::class,'vendor_report'])->name('vendor_report');

Route::get('return', SalesReturn::class)->name('view_return');
Route::get('add_return', [SalesReturn::class,'add_return'])->name('add_return');
Route::post('add_return_item/{id}', [SalesReturn::class,'add_return_item'])->name('add_return_item');
Route::post('receive_return_item/{id}', [SalesReturn::class,'receive_return_item'])->name('receive_return_item');
Route::get('delete_return/{id}', [SalesReturn::class,'delete_return'])->name('delete_return');
Route::get('delete_return_item/{id}', [SalesReturn::class,'delete_return_item'])->name('delete_return_item');
Route::get('return/detail/{id}', [SalesReturn::class,'return_detail'])->name('return_detail');
Route::post('return/ship/{id}', [SalesReturn::class,'return_ship'])->name('return_ship');
Route::post('return/approve/{id}', [SalesReturn::class,'return_approve'])->name('return_approve');
Route::get('return/revert_status/{id}', [SalesReturn::class,'return_revert_status'])->name('return_revert_status');

Route::get('repair', Repair::class)->name('view_repair');
Route::post('add_repair', [Repair::class,'add_repair'])->name('add_repair');
Route::post('check_repair_item/{id}', [Repair::class,'check_repair_item'])->name('add_repair_item');
Route::get('external_repair_receive', [Repair::class,'external_repair_receive'])->name('receive_repair_item');
Route::post('receive_repair_items', [Repair::class,'receive_repair_items'])->name('receive_repair_item');
Route::post('receive_repair_item/{id}', [Repair::class,'receive_repair_item'])->name('receive_repair_item');
Route::post('add_repair_item/{id}', [Repair::class,'add_repair_item'])->name('add_repair_item');
Route::post('repair/add_repair_sheet/{id}', [Repair::class,'add_repair_sheet'])->name('add_repair_item');
Route::get('delete_repair/{id}', [Repair::class,'delete_repair'])->name('delete_repair');
Route::get('delete_repair_item/{id}', [Repair::class,'delete_repair_item'])->name('delete_repair_item');
Route::post('delete_repair_item/{id?}', [Repair::class,'delete_repair_item'])->name('delete_repair_item');
Route::get('repair/detail/{id}', [Repair::class,'repair_detail'])->name('repair_detail');
Route::post('repair/ship/{id}', [Repair::class,'repair_ship'])->name('repair_ship');
Route::post('repair/approve/{id}', [Repair::class,'repair_approve'])->name('repair_approve');
Route::get('export_repair_invoice/{id}/{invoice?}', [Repair::class,'export_repair_invoice'])->name('repair_detail');
Route::get('repair/internal', [Repair::class,'internal_repair'])->name('internal_repair');
Route::post('add_internal_repair_item', [Repair::class,'add_internal_repair_item'])->name('internal_repair');

Route::get('wholesale', Wholesale::class)->name('view_wholesale');
Route::post('add_wholesale', [Wholesale::class,'add_wholesale'])->name('add_wholesale');
Route::post('check_wholesale_item/{id}', [Wholesale::class,'check_wholesale_item'])->name('add_wholesale_item');
Route::post('add_wholesale_item/{id}', [Wholesale::class,'add_wholesale_item'])->name('add_wholesale_item');
Route::get('delete_wholesale/{id}', [Wholesale::class,'delete_order'])->name('delete_wholesale');
Route::get('delete_wholesale_item/{id}', [Wholesale::class,'delete_order_item'])->name('delete_wholesale_item');
Route::get('wholesale/detail/{id}', [Wholesale::class,'wholesale_detail'])->name('wholesale_detail');
Route::post('wholesale/update_prices', [Wholesale::class,'update_prices'])->name('update_wholesale_item');
Route::get('export_bulksale_invoice/{id}/{invoice?}', [Wholesale::class,'export_bulksale_invoice'])->name('wholesale_detail');
Route::get('bulksale_email/{id}', [Wholesale::class,'bulksale_email'])->name('wholesale_detail');
Route::post('wholesale/add_wholesale_sheet/{id}', [Wholesale::class,'add_wholesale_sheet'])->name('add_wholesale_item');
Route::post('wholesale/approve/{id}', [Wholesale::class,'wholesale_approve'])->name('wholesale_approve');
Route::get('wholesale/revert_status/{id}', [Wholesale::class,'wholesale_revert_status'])->name('wholesale_revert_status');
Route::post('wholesale/remove_issues', [Wholesale::class,'remove_issues'])->name('remove_wholesale_issues');

Route::get('pos', [Wholesale::class,'pos'])->name('pos');
Route::get('pos/get_products', [Wholesale::class,'get_products'])->name('pos');
Route::get('pos/get_product_variations/{id}', [Wholesale::class,'get_product_variations'])->name('pos');
Route::post('pos/add', [Wholesale::class,'add'])->name('pos');
Route::post('pos/update', [Wholesale::class,'update'])->name('pos');
Route::post('pos/remove', [Wholesale::class,'remove'])->name('pos');

Route::get('wholesale_return', Wholesale_return::class)->name('view_wholesale_return');
Route::get('add_wholesale_return', [Wholesale_return::class,'add_wholesale_return'])->name('add_wholesale_return');
Route::post('add_wholesale_return_item/{id}', [Wholesale_return::class,'add_wholesale_return_item'])->name('add_wholesale_return_item');
Route::post('receive_wholesale_return_item/{id}', [Wholesale_return::class,'receive_wholesale_return_item'])->name('receive_wholesale_return_item');
Route::get('delete_wholesale_return/{id}', [Wholesale_return::class,'delete_wholesale_return'])->name('delete_wholesale_return');
Route::get('delete_wholesale_return_item/{id}', [Wholesale_return::class,'delete_wholesale_return_item'])->name('delete_wholesale_return_item');
Route::get('wholesale_return/detail/{id}', [Wholesale_return::class,'wholesale_return_detail'])->name('wholesale_return_detail');
Route::post('wholesale_return/ship/{id}', [Wholesale_return::class,'wholesale_return_ship'])->name('wholesale_return_ship');
Route::post('wholesale_return/approve/{id}', [Wholesale_return::class,'wholesale_return_approve'])->name('wholesale_return_approve');
Route::get('wholesale_return/revert_status/{id}', [Wholesale_return::class,'wholesale_return_revert_status'])->name('wholesale_return_revert_status');

Route::get('rma', RMA::class)->name('view_rma');
Route::post('add_rma', [RMA::class,'add_rma'])->name('add_rma');
Route::post('check_rma_item/{id}', [RMA::class,'check_rma_item'])->name('add_rma_item');
Route::post('add_rma_item/{id}', [RMA::class,'add_rma_item'])->name('add_rma_item');
Route::post('return_rma_item/{id}', [RMA::class,'return_rma_item'])->name('return_rma_item');
Route::get('delete_rma/{id}', [RMA::class,'delete_order'])->name('delete_rma');
Route::get('delete_rma_item/{id}', [RMA::class,'delete_order_item'])->name('delete_rma_item');
Route::get('rma/detail/{id}', [RMA::class,'rma_detail'])->name('rma_detail');
Route::post('rma/update_prices', [RMA::class,'update_prices'])->name('update_rma_item');
Route::get('export_rma_invoice/{id}/{invoice?}', [RMA::class,'export_rma_invoice'])->name('rma_detail');
Route::post('rma/approve/{id}', [RMA::class,'rma_approve'])->name('rma_approve');
Route::post('rma/submit/{id}', [RMA::class,'rma_submit'])->name('rma_submit');
Route::get('rma/revert_status/{id}', [RMA::class,'rma_revert_status'])->name('rma_revert_status');

Route::get('imei', IMEI::class)->name('view_imei');
Route::get('imei/rearrange/{id}', [IMEI::class,'rearrange'])->name('rearrange_imei_order');
Route::get('imei/delete_order_item/{id}', [IMEI::class,'delete_order_item'])->name('imei_delete_order_item');
Route::post('imei/refund/{id}', [IMEI::class,'refund'])->name('refund_imei');
Route::post('imei/change_po/{id}', [IMEI::class,'change_po'])->name('change_po_old');
Route::get('imei/print_label', [IMEI::class,'print_label'])->name('view_imei');

Route::get('stock_room', Stock_room::class)->name('view_stock_room');
Route::get('stock_room/exit_scan', [Stock_room::class,'exit_scan'])->name('exit_stock');
Route::get('stock_room/reset_counter', [Stock_room::class,'reset_counter'])->name('exit_stock');
Route::post('stock_room/exit', [Stock_room::class,'exit'])->name('exit_stock');
Route::post('stock_room/receive', [Stock_room::class,'receive'])->name('receive_stock');


Route::get('issue', Issue::class)->name('view_issue');

Route::get('fortnight_return', FortnightReturn::class)->name('view_fortnight_return');
Route::get('fortnight_return/print', [FortnightReturn::class, 'print'])->name('view_fortnight_return');

Route::get('move_inventory', MoveInventory::class)->name('move_inventory');
Route::post('move_inventory/change_grade/{allow_same?}', [MoveInventory::class,'change_grade'])->name('move_inventory');
Route::post('move_inventory/delete_move', [MoveInventory::class,'delete_move'])->name('move_inventory');
Route::post('move_inventory/delete_multiple_moves', [MoveInventory::class,'delete_multiple_moves'])->name('move_inventory');
Route::get('move_inventory/check_storage_change', [MoveInventory::class,'check_storage_change'])->name('move_inventory');

Route::get('testing', Testing::class)->name('view_testing_api_data');
Route::post('testing/upload_excel', [Testing::class, 'upload_excel'])->name('upload_testing_api_data');
Route::get('testing/repush/{id}', [Testing::class, 'repush'])->name('repush_testing_api_data');

Route::get('order', Order::class)->name('view_order');
Route::get('check_new/{return?}', [Order::class,'updateBMOrdersNew'])->name('view_order');
Route::get('refresh_order', [Order::class,'getapiorders'])->name('view_order');
Route::get('refresh_order/{id}', [Order::class,'getapiorders'])->name('view_order');
Route::get('order/refresh/{id?}', [Order::class,'updateBMOrder'])->name('view_order');
Route::post('order/dispatch/{id}', [Order::class,'dispatch'])->name('dispatch_order');
Route::get('order/track/{id}', [Order::class,'track_order'])->name('view_order');
Route::get('order/delete_item/{id}', [Order::class,'delete_item'])->name('delete_order');
Route::post('order/tracking', [Order::class,'tracking'])->name('change_order_tracking');
Route::post('order/correction/{override?}', [Order::class,'correction'])->name('correction');

Route::post('order/replacement/{london?}/{allowed?}', [Order::class,'replacement'])->name('replacement');
Route::get('order/delete_replacement_item/{id}', [Order::class,'delete_replacement_item'])->name('replacement');
Route::get('order/recheck/{id}/{refresh?}/{invoice?}/{tester?}/{data?}/{care?}', [Order::class,'recheck'])->name('view_order');
Route::post('export_order', [Order::class,'export'])->name('dispatch_order');
Route::get('export_note', [Order::class,'export_note'])->name('dispatch_order');
Route::post('export_label', [Order::class,'export_label'])->name('dispatch_order');
Route::get('export_ordersheet', [Order::class,'export_ordersheet'])->name('dispatch_order');
Route::get('export_invoice/{id}', [Order::class,'export_invoice'])->name('dispatch_order');
Route::get('order/export_invoice_new/{id}', [Order::class,'export_invoice_new'])->name('dispatch_order');
Route::get('order/proxy_server', [Order::class,'proxy_server'])->name('dispatch_order');
Route::get('order/export_refund_invoice/{id}', [Order::class,'export_refund_invoice'])->name('dispatch_order');
Route::get('order/label/{id}/{data?}/{update?}', [Order::class,'getLabel'])->name('dispatch_order');

Route::get('sales/allowed', [Order::class,'sales_allowed'])->name('dispatch_admin');
Route::post('order/dispatch_allowed/{id}', [Order::class,'dispatch_allowed'])->name('dispatch_admin');

Route::get('inventory', Inventory::class)->name('view_inventory');
Route::get('inventory/get_products', [Inventory::class,'get_products'])->name('view_inventory');
Route::get('inventory/get_variations/{id}', [Inventory::class,'get_variations'])->name('view_inventory');
Route::get('inventory/inventory_get_vendor_wise_average', [Inventory::class, 'inventoryGetVendorWiseAverage'])->name('view_inventory');
Route::get('inventory/inventory_get_average_cost', [Inventory::class, 'inventoryGetAverageCost'])->name('view_inventory');
Route::post('inventory/export', [Inventory::class,'export'])->name('view_inventory');

Route::get('get_stock_cost/{id}', [Inventory::class,'get_stock_cost'])->name('view_inventory');
Route::get('get_stock_price/{id}', [Inventory::class,'get_stock_price'])->name('view_inventory');

Route::get('inventory/start_verification', [Inventory::class,'start_verification'])->name('inventory_verification');
Route::get('inventory/verification', [Inventory::class,'verification'])->name('inventory_verification');
Route::get('inventory/resume_verification', [Inventory::class,'resume_verification'])->name('inventory_verification');
Route::post('inventory/end_verification', [Inventory::class,'end_verification'])->name('inventory_verification');
Route::post('inventory/add_verification_imei/{id}', [Inventory::class,'add_verification_imei'])->name('inventory_verification');

Route::get('belfast_inventory', [Inventory::class,'belfast_inventory'])->name('view_belfast_inventory');
Route::post('belfast_inventory/aftersale_action/{id}/{action}', [Inventory::class,'aftersale_action'])->name('add_return_item');

Route::get('product', Product::class)->name('view_product');
Route::post('add_product', [Product::class,'add_product'])->name('add_product');
Route::post('product/update_product/{id}', [Product::class,'update_product'])->name('update_product');
Route::post('product/import_product', [Product::class,'import_product'])->name('import_product');

Route::get('variation', Variation::class)->name('view_variation');
Route::post('variation/update_product/{id}', [Variation::class,'update_product'])->name('update_variation');
Route::post('variation/merge/{id}', [Variation::class,'merge'])->name('merge_variation');

// Route::get('listing_old', Listing::class)->name('view_listing');
// Route::get('get_variations', [Listing::class,'get_variations'])->name('view_listing');
// Route::get('get_variation_available_stock/{id}', [Listing::class,'get_variation_available_stock'])->name('view_listing');
// Route::get('listing/get_competitors/{id}', [Listing::class,'get_competitors'])->name('view_listing');
// Route::get('listing/get_sales/{id}', [Listing::class,'get_sales'])->name('view_listing');
// Route::post('listing/update_quantity/{id}', [Listing::class,'update_quantity'])->name('update_listing_quantity');
// Route::post('listing/update_price/{id}', [Listing::class,'update_price'])->name('update_listing_price');

Route::get('listing', [ListingController::class, 'index'])->name('view_listing');
Route::get('listing/get_variations', [ListingController::class, 'get_variations'])->name('view_listing');
Route::get('listing/get_sales/{id}', [ListingController::class, 'get_sales'])->name('view_listing');
Route::get('listing/get_variation_available_stocks/{id}', [ListingController::class, 'get_variation_available_stocks'])->name('view_listing');
Route::get('listing/get_updated_quantity/{id}', [ListingController::class, 'getUpdatedQuantity'])->name('view_listing');
Route::get('listing/get_competitors/{id}/{no_check?}', [ListingController::class, 'getCompetitors'])->name('view_listing');
Route::post('listing/update_quantity/{id}', [ListingController::class,'update_quantity'])->name('update_listing_quantity');
Route::post('listing/update_price/{id}', [ListingController::class,'update_price'])->name('update_listing_price');


Route::get('process', Process::class)->name('view_process');

Route::get('team', Team::class)->name('view_team');
Route::get('add-member', [Team::class,'add_member'])->name('add_member');
Route::post('insert-member', [Team::class,'insert_member'])->name('add_member');
Route::get('update-status/{id}', [Team::class,'update_status'])->name('edit_member');
Route::get('edit-member/{id}', [Team::class,'edit_member'])->name('edit_member');
Route::post('update-member/{id}', [Team::class,'update_member'])->name('edit_member');

Route::get('customer', Customer::class)->name('view_customer');
Route::get('add-customer', [Customer::class,'add_customer'])->name('add_customer');
Route::post('insert-customer', [Customer::class,'insert_customer'])->name('add_customer');
Route::get('edit-customer/{id}', [Customer::class,'edit_customer'])->name('edit_customer');
Route::post('update-customer/{id}', [Customer::class,'update_customer'])->name('edit_customer');
Route::get('customer/delete/{id}', [Customer::class,'delete_customer'])->name('delete_customer');

Route::get('grade', Grade::class)->name('view_grade');
Route::get('add-grade', [Grade::class,'add_grade'])->name('add_grade');
Route::post('insert-grade', [Grade::class,'insert_grade'])->name('add_grade');
Route::get('edit-grade/{id}', [Grade::class,'edit_grade'])->name('edit_grade');
Route::post('update-grade/{id}', [Grade::class,'update_grade'])->name('edit_grade');

Route::get('charge', Charge::class)->name('view_charge');
Route::post('charge/add', [Charge::class,'add_charge'])->name('add_charge');
Route::get('charge/edit/{id}', [Charge::class,'edit_charge'])->name('edit_charge');
Route::post('charge/update/{id}', [Charge::class,'update_charge'])->name('edit_charge');

Route::get('get_permissions/{id}', [Team::class,'get_permissions'])->name('view_permissions');
Route::post('toggle_role_permission/{roleId}/{permissionId}/{isChecked}', [Team::class, 'toggle_role_permission'])->name('change_permission');

Route::post('change', [Change::class,'change_password'])->name('profile');
Route::get('OTP/{any}', [Change::class,'otp'])->name('profile');
Route::get('page', [Change::class,'page'])->name('profile');
Route::post('QomeBa27WU', [Change::class,'reset_page'])->name('profile');
Route::post('reset', [Change::class,'reset_pass'])->name('profile');



Route::get('oauth2/google', [GoogleController::class, 'redirectToGoogle'])->name('google.auth');
Route::get('oauth2/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');


Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);

// });
