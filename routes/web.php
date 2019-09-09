<?php

use App\Garflo\Payments;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

function onSuccess($res)
{
    return var_dump($res);
}
function onError($res)
{
    return var_dump($res);
}

$router->get('/', function () use ($router) {
    return redirect('https://www.systemtour.com');
});

$router->group(['prefix' => 'api/v1'], function () use ($router) {
    /**
     * Test de comunicación con PayU
     */
    $router->get('/ping', function () use ($router) {
        return Payments::doPing('onSuccess', 'onError');
    });

    $router->post('/checkout', 'CheckoutController@index');
    $router->post('/notify/{id}', 'CheckoutController@notify');
    $router->get('/recipt/html/{reference}', 'CheckoutController@getHTMLRecipt');
    $router->get('/recipt/pdf/{reference}', 'CheckoutController@getPDFRecipt');
    // $router->get('/reports', 'ReportController@index');
    // $router->post('/reports/{id}', 'ReportsController@show');

});
