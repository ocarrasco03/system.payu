<?php

namespace App\Http\Controllers;

use App\Garflo\Models\PayerData;
use App\Garflo\Models\PaymentInfo;
use App\Garflo\Models\RequestInfo;
use App\Garflo\Models\TransactionResponse;

class ResourcesController extends Controller
{
    /**
     * Store a new payer
     *
     * @param Request $request
     * @return Response
     */
    public static function storePayer($request)
    {
        $data = PayerData::create([
            'full_name' => $request['name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'address' => $request['address'],
            'city' => $request['city'],
            'state' => $request['state'],
            'country' => $request['country'],
            'zip_code' => $request['zip_code'],
        ]);

        return $data->id;
    }

    /**
     * Store a new request
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public static function storeRequestInfo($request, $id)
    {
        $data = RequestInfo::create([
            'id_system' => $request['label'],
            'id_payer' => $id,
            'id_reservation' => $request['reference'],
            'payment_method' => $request['payment_method'],
            'manual_validation' => false,
        ]);

        return $data->id;
    }

    /**
     * Store a new payment
     *
     * @param Request $request
     * @param int $id
     * @param DateTimestamp $expiration (Optional)
     * @return Response
     */
    public static function storePaymentInfo($request, $id, $expiration = null)
    {
        $data = PaymentInfo::create([
            'id_request_info' => $id,
            'reference_code' => 'reservacion_' . $request['reference'],
            'description' => $request['description'],
            'value' => $request['amount'],
            'currency' => $request['currency'],
            'payment_method' => $request['payment_method'],
            'expiration_date' => $expiration,
            'tax_value' => $request['tax'],
            'tax_return_base' => $request['tax_return'],
        ]);

    }

    /**
     * Store a new transaction
     *
     * @param Request $request
     * @param int $id
     * @param string $globalStatus (Optional)
     * @return Response
     */
    public static function storeTransaction($request, $id, $globalStatus = null)
    {
        $orderId = array_key_exists('orderId', $request->transactionResponse) ? $request->transactionResponse->orderId : null;
        $transactionId = array_key_exists('transactionId', $request->transactionResponse) ? $request->transactionResponse->transactionId : null;
        $responseCode = array_key_exists('responseCode', $request->transactionResponse) ? $request->transactionResponse->responseCode : null;
        $pendingReason = array_key_exists('pendingReason', $request->transactionResponse) ? $request->transactionResponse->pendingReason : null;
        $authorizationCode = array_key_exists('authorization_code', $request->transactionResponse) ? $request->transactionResponse->authorizationCode : null;
        $trazabilityCode = array_key_exists('trazability_code', $request->transactionResponse) ? $request->transactionResponse->trazabilityCode : null;
        $urlPaymentReciptHtml = null;
        $urlPaymentReciptPdf = null;
        if (array_key_exists('extraParameters', $request->transactionResponse)) {
            if (array_key_exists('URL_PAYMENT_RECEIPT_HTML', $request->transactionResponse->extraParameters)) {
                $urlPaymentReciptHtml = $request->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_HTML;
            }
            if (array_key_exists('URL_PAYMENT_RECEIPT_PDF', $request->transactionResponse->extraParameters)) {
                $urlPaymentReciptPdf = $request->transactionResponse->extraParameters->URL_PAYMENT_RECEIPT_PDF;
            }
        }

        $data = TransactionResponse::create([
            'id_request_info' => $id,
            'id_order' => $orderId,
            'id_transaction' => $transactionId,
            'status' => $request->transactionResponse->state,
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
     * Update the given request.
     *
     * @param int $id
     * @return Response
     */
    public static function updateManualPay($id)
    {
        return RequestInfo::find($id)->update(['manual_validation' => true]);
    }

}
