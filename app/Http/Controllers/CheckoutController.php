<?php

namespace App\Http\Controllers;

use App\Garflo\Payments;
use App\Garflo\Models\PayerData;
use App\Garflo\Models\PaymentInfo;
use App\Garflo\Models\RequestInfo;
use App\Garflo\Models\TransactionResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Webiny\Component\Crypt\Crypt;
use PayUParameters;
use PayUPayments;
use Exception;

class CheckoutController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        Payments::setPayUEnvironment();

    }

    /**
     * Array de respuesta
     *
     * @var array
     */
    protected $resJSON = array();

    /**
     * Funcion principal la cual valida la forma de pago, si es en efectivo, trajeta
     * de débito o crédito, ademas recibe todos los parametros necesarios para procesar
     * el pago correspondiente
     *
     * @param Request $request
     * @return void
     */
    public function index(Request $request)
    {
        try {

            $crypt = new Crypt();

            $token = (float) $crypt->decrypt($request->input('amount_to'), Payments::getTokenizer());

            $params = array(
                'reference' => $request->input('reference'),
                'description' => $request->input('description'),
                'currency' => $request->input('currency') != null ? $request->input('currency') : 'MXN',
                'amount' => $request->input('amount'),
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'dni' => $request->input('phone') != null ? $request->input('phone') : '0123456789',
                'phone' => $request->input('phone'),
                'address' => $request->input('address') != null ? $request->input('address') : 'Domicilio conocido s/n',
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'country' => $request->input('country') != null ? $request->input('country') : 'MX',
                'zip_code' => $request->input('zip_code'),
                'payment_method' => $request->input('payment_method'),
                'credit_card' => $request->input('credit_card'),
                'expiration_date' => $request->input('expiration_date'),
                'cvv' => $request->input('cvv'),
                'label' => $request->input('label'),
                'tax' => $request->input('tax'),
                'tax_return' => $request->input('tax_return'),
            );

            // if ($token == $request->input('amount')) {
            if ($request->input('type') == 'cash') {
                $this->validate($request, [
                    'reference' => 'required',
                    'description' => 'required|max:250',
                    'currency' => 'required|string',
                    'amount' => 'required|numeric',
                    'email' => 'required|email',
                    'name' => 'required|string',
                    'phone' => 'nullable|numeric',
                    'payment_method' => 'required',
                    'tax' => 'nullable|numeric',
                    'tax_return' => 'nullable|numeric',
                    'label' => 'required|numeric',
                ]);

                return $this->payWithCash($params);

            }
            
            if ($request->input('type') == 'creditCard') {
                $this->validate($request, [
                    'reference' => 'required',
                    'description' => 'required|max:250',
                    'currency' => 'required|string',
                    'amount' => 'required|numeric',
                    'email' => 'required|email',
                    'name' => 'required|string',
                    'phone' => 'nullable|numeric',
                    'payment_method' => 'required',
                    'credit_card' => 'required|numeric',
                    'expiration_date' => 'required',
                    'cvv' => 'required|numeric',
                    'label' => 'required|numeric',
                ]);

                return $this->payWithCreditCard($params);

            } else if ($request->input('type') == null) {
                $message = 'Oops! No se recibio un tipo de pago';
                throw new Exception($message);
            }
            // } else {
            //     $message = 'Oops! el token no coincide';
            //     throw new Exception($message);
            // }
        } catch (Exception $e) {
            // $resJSON['error'] = $e;
            $resJSON['errorMessage'] = $e->getMessage();

            return response()->json($resJSON, 422);
        }
    }

    /**
     * Funcion para pagos en efectivo via OXXO, SEVEN ELEVEN o pagos en bancos
     * como SANTANDER, SCOTIABANK o BANCOMER.
     *
     *
     * @param array $request
     * @return void
     */
    public function payWithCash($request)
    {

        try {
            if (!PayerData::PayerExist($request['email'])) {
                $payerId = $this->storePayer($request);
            } else {
                $payerId = PayerData::GetId($request['email']);
                foreach ($payerId as $data) {
                    $payerId = $data['id'];
                }
            }

            if (!RequestInfo::RequestExist($request['reference'])) {
                $requestId = $this->storeRequestInfo($request, $payerId);
            } else {
                $requestId = RequestInfo::getId($request['reference']);
                foreach ($requestId as $data) {
                    $requestId = $data['id'];
                }
            }

            $data = array(
                PayUParameters::ACCOUNT_ID => Payments::getAccountId(),
                PayUParameters::REFERENCE_CODE => $request['reference'],
                PayUParameters::DESCRIPTION => $request['description'],
                PayUParameters::CURRENCY => $request['currency'],
                PayUParameters::VALUE => $request['amount'],
                PayUParameters::BUYER_EMAIL => $request['email'],
                PayUParameters::PAYER_NAME => $request['name'],
                PayUParameters::PAYER_DNI => $request['dni'],
                PayUParameters::PAYMENT_METHOD => $request['payment_method'],
                PayUParameters::COUNTRY => Payments::getCountry(),
                PayUParameters::EXPIRATION_DATE => substr(date_format(Carbon::now()->addHours(24)->addMinutes(5), 'c'), 0, -6),
                // PayUParameters::EXPIRATION_DATE => substr(date_format(Carbon::now()->addHours(1), 'c'), 0, -6),
                PayUParameters::IP_ADDRESS => '127.0.0.1',
                // PayUParameters::NOTIFY_URL => 'https://api.rodason.com/api/v1/notify/' . $requestId,
            );

            $response = PayUPayments::doAuthorizationAndCapture($data);

            if ($response) {
                if (!File::isDirectory('payWithCash')) {
                    mkdir('payWithCash', 0755);
                }
                $this->logResponse($response, 'payWithCash\\payWithCash');
                if ($response->transactionResponse->state == 'PENDING') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['orderId'] = $response->transactionResponse->orderId;
                    $resJSON['transactionId'] = $response->transactionResponse->transactionId;
                    $resJSON['pendingReason'] = $response->transactionResponse->pendingReason;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    if (array_key_exists($response->transactionResponse->responseCode, Payments::$pendingResponseCode)) {
                        $resJSON['message'] = Payments::$pendingResponseCode[$response->transactionResponse->responseCode];
                    }
                    if (array_key_exists('extraParameters', $response->transactionResponse)) {
                        if (array_key_exists('URL_PAYMENT_RECEIPT_HTML', $response->transactionResponse->extraParameters)) {
                            $resJSON['urlPaymentReceiptHtml'] = $response->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_HTML;
                        }
                    }
                    if (array_key_exists('extraParameters', $response->transactionResponse)) {
                        if (array_key_exists('URL_PAYMENT_RECEIPT_PDF', $response->transactionResponse->extraParameters)) {
                            $resJSON['urlPaymentReceiptPdf'] = $response->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_PDF;
                        }
                    }
                    $resJSON['operationDate'] = $response->transactionResponse->operationDate;
                    $resJSON['expirationDate'] = $response->transactionResponse->extraParameters->EXPIRATION_DATE;
                } else if ($response->transactionResponse->state == 'DECLINED') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['orderId'] = $response->transactionResponse->orderId;
                    $resJSON['transactionId'] = $response->transactionResponse->transactionId;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    if (array_key_exists($response->transactionResponse->responseCode, Payments::$declinedResponseCode)) {
                        $resJSON['message'] = Payments::$declinedResponseCode[$response->transactionResponse->responseCode];
                    }
                    if (array_key_exists('responseMessage', $response->transactionResponse)) {
                        $resJSON['responseMessage'] = $response->transactionResponse->responseMessage;
                    }
                } else if ($response->transactionResponse->state == 'ERROR') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    if (array_key_exists('responseMessage', $response->transactionResponse)) {
                        $resJSON['responseMessage'] = $response->transactionResponse->responseMessage;
                    }
                    if (array_key_exists($response->transactionResponse->responseCode, Payments::$errorResponseCode)) {
                        $resJSON['message'] = Payments::$errorResponseCode[$response->transactionResponse->responseCode];
                    }
                } else if ($response->transactionResponse->state == 'EXPIRED') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    $resJSON['errorCode'] = $response->transactionResponse->errorCode;
                    if (array_key_exists($response->transactionResponse->errorCode, Payments::$expiredResponseCode)) {
                        $resJSON['errorResponse'] = Payments::$expiredResponseCode[$response->transactionResponse->errorCode];
                    }
                } else if ($response->transactionResponse->state == 'APPROVED') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['orderId'] = $response->transactionResponse->orderId;
                    $resJSON['transactionId'] = $response->transactionResponse->transactionId;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                }

                if (!PaymentInfo::PaymentExist($requestId)) {
                    $expiration = null;
                    if (array_key_exists('extraParameters', $response->transactionResponse)) {
                        $expiration = (array_key_exists('EXPIRATION_DATE', $response->transactionResponse->extraParameters)) ? $response->transactionResponse->extraParameters->EXPIRATION_DATE : null;
                    }

                    $this->storePaymentInfo($request, $requestId, $expiration);
                }

                $this->storeTransaction($response, $requestId);

            } else {
                $resJSON['status'] = 'error';
                $resJSON['message'] = 'Lo sentimos algo salio mal';

                return response()->json($resJSON, 409);
            }

            return response()->json($resJSON, 200);
        } catch (Exception $e) {
            if (!File::isDirectory('payWithCash')) {
                mkdir('payWithCash', 0755);
            }

            $this->logResponse($e, 'payWithCash\\log_');

            $resJSON['errorMessage'] = $e;
            $resJSON['errorParams'] = $data;
            return response()->json($resJSON, 409);
        }
    }

    /**
     * Función para pagos via tarjeta de crédito débito, ya sean VISA,
     * MASTERCARD o AMERICAN EXPRESS.
     *
     * @param array $request
     * @return void
     */
    public function payWithCreditCard($request)
    {
        if (!File::isDirectory('payWithCard')) {
            mkdir('payWithCard', 0755);
        }

        try {
            if (!PayerData::PayerExist($request['email'])) {
                $payerId = $this->storePayer($request);
            } else {
                $payerId = PayerData::GetId($request['email']);
                foreach ($payerId as $data) {
                    $payerId = $data['id'];
                }
            }

            if (!RequestInfo::RequestExist($request['reference'])) {
                $requestId = $this->storeRequestInfo($request, $payerId);
            } else {
                $requestId = RequestInfo::getId($request['reference']);
                foreach ($requestId as $data) {
                    $requestId = $data['id'];
                }
            }

            $data = array(
                PayUParameters::ACCOUNT_ID => Payments::getAccountId(),
                PayUParameters::REFERENCE_CODE => $request['reference'],
                PayUParameters::DESCRIPTION => $request['description'],
                PayUParameters::CURRENCY => $request['currency'],
                PayUParameters::VALUE => $request['amount'],
                PayUParameters::BUYER_EMAIL => $request['email'],
                PayUParameters::BUYER_NAME => $request['name'],
                PayUParameters::BUYER_CONTACT_PHONE => $request['phone'],
                PayUParameters::BUYER_DNI => $request['dni'],
                PayUParameters::BUYER_STREET => $request['address'],
                PayUParameters::BUYER_CITY => $request['city'],
                PayUParameters::BUYER_STATE => $request['state'],
                PayUParameters::BUYER_COUNTRY => $request['country'],
                PayUParameters::BUYER_POSTAL_CODE => $request['zip_code'],
                PayUParameters::BUYER_PHONE => $request['phone'],
                PayUParameters::PAYER_EMAIL => $request['email'],
                PayUParameters::PAYER_NAME => $request['name'],
                PayUParameters::PAYER_CONTACT_PHONE => $request['phone'],
                PayUParameters::PAYER_DNI => $request['dni'],
                PayUParameters::PAYER_STREET => $request['address'],
                PayUParameters::PAYER_CITY => $request['city'],
                PayUParameters::PAYER_STATE => $request['state'],
                PayUParameters::PAYER_COUNTRY => $request['country'],
                PayUParameters::PAYER_POSTAL_CODE => $request['zip_code'],
                PayUParameters::PAYER_PHONE => $request['phone'],
                PayUParameters::CREDIT_CARD_NUMBER => $request['credit_card'],
                PayUParameters::CREDIT_CARD_EXPIRATION_DATE => $request['expiration_date'],
                PayUParameters::CREDIT_CARD_SECURITY_CODE => $request['cvv'],
                PayUParameters::PAYMENT_METHOD => $request['payment_method'],
                PayUParameters::INSTALLMENTS_NUMBER => '1',
                PayUParameters::COUNTRY => Payments::getCountry(),
                PayUParameters::IP_ADDRESS => '127.0.0.1',
                PayUParameters::NOTIFY_URL => 'https://api.rodason.com/api/v1/notify/' . $requestId,
                PayUParameters::USER_AGENT => $_SERVER['HTTP_USER_AGENT'],
            );

            $response = PayUPayments::doAuthorizationAndCapture($data);

            if ($response) {
                $this->logResponse($response, 'payWithCard\\payWithCard_');

                if ($response->transactionResponse->state == 'PENDING') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['orderId'] = $response->transactionResponse->orderId;
                    $resJSON['transactionId'] = $response->transactionResponse->transactionId;
                    $resJSON['pendingReason'] = $response->transactionResponse->pendingReason;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    if (array_key_exists($response->transactionResponse->responseCode, Payments::$pendingResponseCode)) {
                        $resJSON['message'] = Payments::$pendingResponseCode[$response->transactionResponse->responseCode];
                    }
                } else if ($response->transactionResponse->state == 'APPROVED') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['orderId'] = $response->transactionResponse->orderId;
                    $resJSON['transactionId'] = $response->transactionResponse->transactionId;
                    $resJSON['trazabilityCode'] = $response->transactionResponse->trazabilityCode;
                    $resJSON['authorizationCode'] = $response->transactionResponse->authorizationCode;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    if (array_key_exists($response->transactionResponse->responseCode, Payments::$approvedResponseCode)) {
                        $resJSON['message'] = Payments::$approvedResponseCode[$response->transactionResponse->responseCode];
                    }
                } else if ($response->transactionResponse->state == 'DECLINED') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['orderId'] = $response->transactionResponse->orderId;
                    $resJSON['transactionId'] = $response->transactionResponse->transactionId;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    if (array_key_exists($response->transactionResponse->responseCode, Payments::$declinedResponseCode)) {
                        $resJSON['message'] = Payments::$declinedResponseCode[$response->transactionResponse->responseCode];
                    }
                    if (array_key_exists('paymentNetworkResponseCode', $response->transactionResponse)) {
                        $resJSON['paymentNetworkResponseCode'] = $response->transactionResponse->paymentNetworkResponseCode;
                    }
                    if (array_key_exists('paymentNetworkResponseErrorMessage', $response->transactionResponse)) {
                        $resJSON['paymentNetworkResponseErrorMessage'] = $response->transactionResponse->paymentNetworkResponseErrorMessage;
                    }
                    $resJSON['responseMessage'] = $response->transactionResponse->responseMessage;
                } else if ($response->transactionResponse->state == 'ERROR') {
                    $resJSON['status'] = $response->transactionResponse->state;
                }

                if (!PaymentInfo::PaymentExist($requestId)) {
                    $expiration = null;
                    if (array_key_exists('extraParameters', $response->transactionResponse)) {
                        $expiration = (array_key_exists('EXPIRATION_DATE', $response->transactionResponse->extraParameters)) ? $response->transactionResponse->extraParameters->EXPIRATION_DATE : null;
                    }

                    $this->storePaymentInfo($request, $requestId, $expiration);
                }

                $this->storeTransaction($response, $requestId);

            } else {
                $resJSON['status'] = 'error';
                $resJSON['message'] = 'Lo sentimos algo salio mal';

                return response()->json($resJSON, 409);
            }

            return response()->json($resJSON, 200);
        } catch (Exception $e) {
            $resJSON['errorMessage'] = $e->getMessage();
            return response()->json($resJSON, 409);
        }
    }

    /**
     * Stores a newly created resource in storage.
     *
     * @param array $params
     * @return void
     */
    public function storePayer($params)
    {
        $data = PayerData::create([
            'nombre_completo' => $params['name'],
            'email' => $params['email'],
            'telefono' => $params['phone'],
            'address' => $params['address'],
            'city' => $params['city'],
            'state' => $params['state'],
            'country' => $params['country'],
            'zip_code' => $params['zip_code'],
        ]);

        return $data->id;
    }

    public function storeRequestInfo($params, $idPayer)
    {
        $data = RequestInfo::create([
            'id_systems' => $params['label'],
            'id_payer' => $idPayer,
            'tipo_pago' => $params['payment_method'],
            'id_reservacion' => $params['reference'],
        ]);

        return $data->id;
    }

    public function storePaymentInfo($params, $idRequestInfo, $expiration = null)
    {
        $data = PaymentInfo::create([
            'id_request_info' => $idRequestInfo,
            'reference_code' => 'reservacion_' . $params['reference'],
            'description' => $params['description'],
            'value' => $params['amount'],
            'currency' => $params['currency'],
            'payment_method' => $params['payment_method'],
            'expiration_date' => $expiration,
            'tax_value' => $params['tax'],
            'tax_return_base' => $params['tax_return'],
        ]);

    }

    public function storeTransaction($response, $idRequestInfo)
    {
        $orderId = array_key_exists('orderId', $response->transactionResponse) ? $response->transactionResponse->orderId : null;
        $transactionId = array_key_exists('transactionId', $response->transactionResponse) ? $response->transactionResponse->transactionId : null;
        $responseCode = array_key_exists('responseCode', $response->transactionResponse) ? $response->transactionResponse->responseCode : null;
        $pendingReason = array_key_exists('pendingReason', $response->transactionResponse) ? $response->transactionResponse->pendingReason : null;
        $urlPaymentReciptHtml = null;
        if (array_key_exists('extraParameters', $response->transactionResponse)) {
            if (array_key_exists('URL_PAYMENT_RECEIPT_HTML', $response->transactionResponse->extraParameters)) {
                $urlPaymentReciptHtml = $response->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_HTML;
            }
        }

        $data = TransactionResponse::create([
            'id_request_info' => $idRequestInfo,
            'id_order' => $orderId,
            'id_transaction' => $transactionId,
            'status' => $response->transactionResponse->state,
            'response_code' => $responseCode,
            'pending_reason' => $pendingReason,
            'url_payment_recipt_html' => $urlPaymentReciptHtml,
        ]);
    }

    public function notify(Request $request, $id)
    {

        if (!File::isDirectory('notify')) {
            mkdir('notify', 0755);
        }

        try {

            $this->logResponse($request->all(), 'notify\\notify');

            $state = null;

            if ($request->state_pol == Payments::$statePolApproved) {
                $state = 'APPROVED';
            } else if ($request->state_pol == Payments::$statePolDeclined) {
                $state = 'DECLINED';
            } else if ($request->state_pol == Payments::$statePolExpired) {
                $state = 'EXPIRED';
            } else if ($request->state_pol == Payments::$statePolPending || $request->state_pol == Payments::$statePolPendingSent || $request->state_pol == Payments::$statePolPendingAwaiting || $request->state_pol == Payments::$statePolPendingPaymentEntity || $request->state_pol == Payments::$statePolPendingPaymentBank || $request->state_pol == Payments::$statePolPendingNotifying) {
                $state = 'PENDING';
            } else if ($request->state_pol == Payments::$statePolError) {
                $state = 'ERROR';
            }

            $response = (object) array(
                'transactionResponse' => (object) array(
                    'state' => $state,
                    'orderId' => (int) $request->reference_pol,
                    'transactionId' => $request->transaction_id,
                    'responseCode' => $request->response_message_pol,
                ),
            );

            $this->storeTransaction($response, $id);
        } catch (Exception $e) {
            $this->logResponse($e->getMessage(), 'notify\\log_');
            $this->logResponse($response, 'notify\\log_');

        }

        return response()->json($response, 200);
    }

    public function logResponse($request, $type)
    {
        $fp = fopen($type . Carbon::now()->format('Y-m-d') . '.json', 'a+');
        fwrite($fp, json_encode($request) . "\r\n");
        fclose($fp);

    }
}