<?php

use App\Garflo\Payments;
use Webiny\Component\Crypt\Crypt;

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
    $router->get('/ping', function() use ($router) {
        return Payments::doPing('onSuccess', 'onError');
    });
    
});