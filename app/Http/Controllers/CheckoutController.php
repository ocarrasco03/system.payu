<?php

namespace App\Http\Controllers;

use App\Garflo\Models\PayerData;
use App\Garflo\Models\PaymentInfo;
use App\Garflo\Models\RequestInfo;
use App\Garflo\Models\TransactionResponse;
use App\Garflo\Payments;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Helpers;
use Illuminate\Http\Request;
use InvalidArgumentException;
use PayUParameters;
use PayUPayments;
use Webiny\Component\Crypt\Crypt;

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

            if ($token == $request->input('amount')) {
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

                    if (!$this->validateProcess($request->input('reference'))) {
                        return $this->payWithCash($params);
                    } else {
                        $data = $this->validateProcess($request->input('reference'));
                        $resJSON['status'] = $data['status'];
                        $resJSON['orderId'] = $data['id_order'];
                        $resJSON['transactionId'] = $data['id_transaction'];
                        $data['status'] == 'PENDING' ? $resJSON['pendingReason'] = $data['pending_reason'] : null;
                        $data['status'] == 'PENDING' ? $resJSON['urlPaymentReceiptHtml'] = $data['url_payment_recipt_html'] : null;
                        $data['status'] == 'PENDING' ? $resJSON['urlPaymentReceiptPdf'] = $data['url_payment_recipt_pdf'] : null;
                        $data['status'] == 'PENDING' ? $resJSON['expirationDate'] = Carbon::createFromFormat('Y-m-d H:i:s', $data['created_at'])->addHours(19)->addMinutes(5)->toDateTimeString() : null;
                        $resJSON['responseCode'] = $data['response_code'];
                        if (array_key_exists($data['response_code'], Payments::$pendingResponseCode)) {
                            $resJSON['message'] = Payments::$pendingResponseCode[$data['response_code']];
                        }
                        $data['status'] == 'APPROVED' ? $resJSON['trazabilityCode'] = $data['trazability_code'] : null;
                        $data['status'] == 'APPROVED' ? $resJSON['authorizationCode'] = $data['authorization_code'] : null;
                        return response()->json($resJSON, 200);

                    }

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

                    if (!$this->validateProcess($request->input('reference'))) {
                        return $this->payWithCreditCard($params);
                    } else {
                        $data = $this->validateProcess($request->input('reference'));
                        $resJSON['status'] = $data['status'];
                        $resJSON['orderId'] = $data['id_order'];
                        $resJSON['transactionId'] = $data['id_transaction'];
                        $data['status'] == 'PENDING' ? $resJSON['pendingReason'] = $data['pending_reason'] : null;
                        $resJSON['responseCode'] = $data['response_code'];
                        if (array_key_exists($data['response_code'], Payments::$pendingResponseCode)) {
                            $resJSON['message'] = Payments::$pendingResponseCode[$data['response_code']];
                        }
                        $data['status'] == 'APPROVED' ? $resJSON['trazabilityCode'] = $data['trazability_code'] : null;
                        $data['status'] == 'APPROVED' ? $resJSON['authorizationCode'] = $data['authorization_code'] : null;
                        return response()->json($resJSON, 200);
                    }

                } else if ($request->input('type') == null) {
                    $message = 'Oops! No se recibio un tipo de pago';
                    throw new Exception($message);
                }
            } else {
                $message = 'Oops! el token no coincide';
                throw new Exception($message);
            }
        } catch (Exception $e) {
            $resJSON['errorMessage'] = $e->getMessage();
            return response()->json($resJSON, 422);
        } catch (InvalidArgumentException $e) {
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
                PayUParameters::EXPIRATION_DATE => substr(date_format(Carbon::now()->addHours(19)->addMinutes(5), 'c'), 0, -6),
                PayUParameters::IP_ADDRESS => Helpers::getUserIP(),
                PayUParameters::NOTIFY_URL => 'https://api.rodason.com/api/v1/notify/' . $requestId,
            );

            $response = PayUPayments::doAuthorizationAndCapture($data);

            if ($response) {
                Helpers::logResponse($response, 'payWithCash', 'payWithCash', $data);
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
            Helpers::logResponse($e->getMessage(), 'payWithCash', 'log', $data);

            $resJSON['errorMessage'] = $e->getMessage();
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
                PayUParameters::IP_ADDRESS => Helpers::getUserIP(),
                PayUParameters::NOTIFY_URL => 'https://api.rodason.com/api/v1/notify/' . $requestId,
                PayUParameters::USER_AGENT => $_SERVER['HTTP_USER_AGENT'],
            );

            $response = PayUPayments::doAuthorizationAndCapture($data);

            if ($response) {
                Helpers::logResponse($response, 'payWithCard', 'payWithCard', $data);

                if ($response->transactionResponse->state == 'PENDING') {
                    $resJSON['status'] = $response->transactionResponse->state;
                    $resJSON['orderId'] = $response->transactionResponse->orderId;
                    $resJSON['transactionId'] = $response->transactionResponse->transactionId;
                    $resJSON['pendingReason'] = $response->transactionResponse->pendingReason;
                    $resJSON['responseCode'] = $response->transactionResponse->responseCode;
                    if (array_key_exists($response->transactionResponse->responseCode, Payments::$pendingResponseCode)) {
                        $resJSON['message'] = Payments::$pendingResponseCode[$response->transactionResponse->responseCode];
                    }
                    ResourcesController::updateManualPay($requestId);
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
            Helpers::logResponse($e->getMessage(), 'payWithCard', 'log', $data);
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
            'full_name' => $params['name'],
            'email' => $params['email'],
            'phone' => $params['phone'],
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
            'id_system' => $params['label'],
            'id_payer' => $idPayer,
            'id_reservation' => $params['reference'],
            'payment_method' => $params['payment_method'],
            'manual_validation' => false,
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

    public function storeTransaction($response, $idRequestInfo, $globalStatus = null)
    {
        $orderId = array_key_exists('orderId', $response->transactionResponse) ? $response->transactionResponse->orderId : null;
        $transactionId = array_key_exists('transactionId', $response->transactionResponse) ? $response->transactionResponse->transactionId : null;
        $responseCode = array_key_exists('responseCode', $response->transactionResponse) ? $response->transactionResponse->responseCode : null;
        $pendingReason = array_key_exists('pendingReason', $response->transactionResponse) ? $response->transactionResponse->pendingReason : null;
        $authorizationCode = array_key_exists('authorization_code', $response->transactionResponse) ? $response->transactionResponse->authorizationCode : null;
        $trazabilityCode = array_key_exists('trazability_code', $response->transactionResponse) ? $response->transactionResponse->trazabilityCode : null;
        $urlPaymentReciptHtml = null;
        $urlPaymentReciptPdf = null;
        if (array_key_exists('extraParameters', $response->transactionResponse)) {
            if (array_key_exists('URL_PAYMENT_RECEIPT_HTML', $response->transactionResponse->extraParameters)) {
                $urlPaymentReciptHtml = $response->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_HTML;
            }
            if (array_key_exists('URL_PAYMENT_RECEIPT_PDF', $response->transactionResponse->extraParameters)) {
                $urlPaymentReciptPdf = $response->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_PDF;
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
            'url_payment_recipt_pdf' => $urlPaymentReciptPdf,
            'authorization_code' => $authorizationCode,
            'trazability_code' => $trazabilityCode,
            'global_update' => $globalStatus,
        ]);
    }

    /**
     * Esta funcion recibe la respuesta del proveedor de pagos PayU y solicita la actualizacion del
     * estatus de la reserva en globalizador, ademas crea un registro en la base de datos con los
     * datos de la respuesta.
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function notify(Request $request, $id)
    {
        try {

            Helpers::logResponse($request->all(), 'notify', 'notify');

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

            $client = new Client([
                'base_uri' => 'http://www.systemtour.demo/mx/api/confirmation/update-reserva.php',
                'timeout' => 120.0,
            ]);

            $res_glob = $client->request('POST', '', [
                'form_params' => [
                    'status' => $state,
                    'reference' => $request->reference_sale,
                    'transactionId' => $request->transaction_id,
                ],
            ]);
            $body = $res_glob->getBody();
            $body = json_decode($body);

            Helpers::logResponse($body, 'notify', 'global_log');
            $this->storeTransaction($response, $id, $body->message);

            return response()->json($response, 200);

        } catch (Exception $e) {
            Helpers::logResponse($e->getMessage(), 'notify', 'log');
            Helpers::logResponse($response, 'notify', 'log');
            return response()->json($response, 409);
        }

    }

    /**
     * This function validates the reference code doesn't have previous transactions
     *
     * @param string $reference
     * @return bool
     */
    public function validateProcess($reference)
    {
        if (RequestInfo::RequestExist($reference)) {
            $reqId = RequestInfo::getId($reference);
            foreach ($reqId as $data) {
                $reqId = $data['id'];
            }

            if (TransactionResponse::requestHasTransactions($reqId)) {
                $transactions = TransactionResponse::getTransactionStatusByRequest($reqId);
                foreach ($transactions as $data) {
                    if ($data['status'] == 'PENDING' || $data['status'] == 'APPROVED') {
                        return $data;
                    }
                }

                return false;
            }

            return false;
        }

        return false;
    }

    /**
     * This function returns the URL recipt on PDF
     *
     * @param string $reference
     * @return string
     */
    public function getPDFRecipt($reference)
    {
        $requestId = RequestInfo::getId($reference);
        foreach ($requestId as $data) {
            $requestId = $data['id'];
        }
        $urls = TransactionResponse::select('url_payment_recipt_pdf')->whereNotNull('url_payment_recipt_pdf')->where('id_request_info', $requestId)->get();
        $recipt = null;

        foreach ($urls as $url) {
            $recipt = $url['url_payment_recipt_pdf'];
        }

        $resJSON['urlPaymentReceiptPdf'] = $recipt;

        return response()->json($resJSON, 200);
    }

    /**
     * This function returns the URL recipt on HTML
     *
     * @param string $reference
     * @return string
     */
    public function getHTMLRecipt($reference)
    {
        $requestId = RequestInfo::getId($reference);
        foreach ($requestId as $data) {
            $requestId = $data['id'];
        }
        $urls = TransactionResponse::select('url_payment_recipt_html')->whereNotNull('url_payment_recipt_html')->where('id_request_info', $requestId)->get();
        $recipt = null;

        foreach ($urls as $url) {
            $recipt = $url['url_payment_recipt_html'];
        }

        $resJSON['urlPaymentReceiptHtml'] = $recipt;

        return response()->json($resJSON, 200);

    }

}
