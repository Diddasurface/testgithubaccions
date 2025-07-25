<?php
use App\Http\Controllers\Tenant\ConfigurationImageController;

use App\Http\Controllers\Tenant\ItemController;

Route::prefix('barcode-config')->group(function () {
    Route::get('{columns}', [ItemController::class, 'getBarcodeConfig']);
    Route::post('{columns}', [ItemController::class, 'saveBarcodeConfig']);
});
Route::get('generate_token', 'Tenant\Api\MobileController@getSeries');

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);
if ($hostname) {
    Route::domain($hostname->fqdn)->group(function () {
        Route::post('configurations/default-image', [ConfigurationImageController::class, 'upload']);
        Route::post('login', 'Tenant\Api\MobileController@login');

        Route::middleware(['auth:api', 'locked.tenant'])->group(function () {
            //MOBILE
            Route::get('record/qrapi', 'Tenant\Api\MobileController@record_qrapi');
            Route::get('document/series', 'Tenant\Api\MobileController@getSeries');
            Route::get('document/series-dispatch', 'Tenant\Api\MobileController@getSeriesDispatch');
            Route::get('document/paymentmethod', 'Tenant\Api\MobileController@getPaymentmethod');
            Route::get('document/tables', 'Tenant\Api\MobileController@tables');
            Route::get('document/customers', 'Tenant\Api\MobileController@customers');
            Route::post('document/email', 'Tenant\Api\MobileController@document_email');
            Route::post('sale-note', 'Tenant\Api\SaleNoteController@store');
            Route::get('sale-note/series', 'Tenant\Api\SaleNoteController@series');
            Route::get('sale-note/lists', 'Tenant\Api\SaleNoteController@lists');
            Route::post('item', 'Tenant\Api\MobileController@item');
            Route::post('items/{id}/update', 'Tenant\Api\MobileController@updateItem');
            Route::post('item/upload', 'Tenant\Api\MobileController@upload');
            Route::post('person', 'Tenant\Api\MobileController@person');
            Route::get('document/search-items', 'Tenant\Api\MobileController@searchItems');
            Route::get('document/search-customers', 'Tenant\Api\MobileController@searchCustomers');
            Route::post('sale-note/email', 'Tenant\Api\SaleNoteController@email');
            Route::post('sale-note/{id}/generate-cpe', 'Tenant\Api\SaleNoteController@generateCPE');
            Route::get('document/get-data/{id}', 'Tenant\Api\MobileController@getDataToDispatch');

            Route::get('report', 'Tenant\Api\MobileController@report');

            Route::post('documents', 'Tenant\Api\DocumentController@store');
            Route::get('documents/lists', 'Tenant\Api\DocumentController@lists');
            Route::get('documents/lists/{startDate}/{endDate}', 'Tenant\Api\DocumentController@lists');
            Route::post('documents/updatedocumentstatus', 'Tenant\Api\DocumentController@updatestatus');
            Route::post('summaries', 'Tenant\Api\SummaryController@store');
            Route::post('voided', 'Tenant\Api\VoidedController@store');
            Route::post('retentions', 'Tenant\Api\RetentionController@store');
            Route::post('dispatches', 'Tenant\Api\DispatchController@store');
            Route::post('documents/send', 'Tenant\Api\DocumentController@send');
            Route::post('summaries/status', 'Tenant\Api\SummaryController@status');
            Route::post('voided/status', 'Tenant\Api\VoidedController@status');
            Route::get('services/ruc/{number}', 'Tenant\Api\ServiceController@ruc');
            Route::get('services/dni/{number}', 'Tenant\Api\ServiceController@dni');
            Route::post('services/consult_cdr_status', 'Tenant\Api\ServiceController@consultCdrStatus');
            Route::post('services/validate_cpe', 'Tenant\Api\ServiceController@validateCpe');
            Route::post('perceptions', 'Tenant\Api\PerceptionController@store');

            Route::post('dispatches/send', 'Tenant\Api\DispatchController@send');
            Route::post('dispatches/status_ticket', 'Tenant\Api\DispatchController@statusTicket');
            Route::get('dispatches/tables', 'Tenant\Api\DispatchController@tables');
            Route::get('dispatches/records', 'Tenant\Api\DispatchController@records');

            Route::post('documents_server', 'Tenant\Api\DocumentController@storeServer');
            Route::get('document_check_server/{external_id}', 'Tenant\Api\DocumentController@documentCheckServer');

            //liquidacion de compra
            Route::post('purchase-settlements', 'Tenant\Api\PurchaseSettlementController@store');

            //Pedidos
            Route::get('orders', 'Tenant\Api\OrderController@records');
            Route::post('orders', 'Tenant\Api\OrderController@store');

            //Company
            Route::get('company', 'Tenant\Api\CompanyController@record');

            // Cotizaciones
            Route::get('quotations/list', 'Tenant\Api\QuotationController@list');
            Route::post('quotations', 'Tenant\Api\QuotationController@store');
            Route::post('quotations/email', 'Tenant\Api\QuotationController@email');
            Route::get('quotations/tables', 'Tenant\Api\QuotationController@tables');

            //Caja
            Route::post('cash/restaurant', 'Tenant\Api\CashController@storeRestaurant');
            Route::post('cash/cash_document', 'Tenant\Api\CashController@cash_document');
            Route::get('cash/opening_cash', 'Tenant\Api\CashController@opening_cash');
            Route::get('cash/opening_cash_check/{cash_id}', 'Tenant\Api\CashController@opening_cash_check');
            Route::get('cash/available-restaurant', 'Tenant\Api\CashController@cash_available');
            Route::post('cash/open', 'Tenant\CashController@store');
            Route::get('cash/close/{cash}', 'Tenant\Api\CashController@close');

            //Vendeya
            Route::prefix('sellnow')->group(function () {
                Route::get('/items', 'Tenant\Api\SellnowController@items');
                Route::get('/categories', 'Tenant\Api\SellnowController@categories');
                Route::post('/favoriteitem', 'Tenant\Api\SellnowController@setFavoriteItem');
            });

        });
        Route::get('documents/search/customers', 'Tenant\DocumentController@searchCustomers');

        // Route::post('services/consult_status', 'Tenant\Api\ServiceController@consultStatus');
        Route::post('documents/status', 'Tenant\Api\ServiceController@documentStatus');

        Route::get('sendserver/{document_id}/{query?}', 'Tenant\DocumentController@sendServer');
        Route::post('configurations/generateDispatch', 'Tenant\ConfigurationController@generateDispatch');

        
        // Contenido de los certificados de qz tray
        Route::get('certificates-qztray/private', 'Tenant\CertificateQzTrayController@private');
        Route::get('certificates-qztray/digital', 'Tenant\CertificateQzTrayController@digital');

    });
} else {
    Route::domain(env('APP_URL_BASE'))->group(function () {


        Route::middleware(['auth:system_api'])->group(function () {

            //reseller
            Route::post('reseller/detail', 'System\Api\ResellerController@resellerDetail');
            Route::post('reseller/lockedAdmin', 'System\Api\ResellerController@lockedAdmin');
            Route::post('reseller/lockedTenant', 'System\Api\ResellerController@lockedTenant');

            Route::get('restaurant/partner/list', 'System\Api\RestaurantPartnerController@list');
            Route::post('restaurant/partner/store', 'System\Api\RestaurantPartnerController@store');
            Route::post('restaurant/partner/search', 'System\Api\RestaurantPartnerController@search');

        });

    });

}
