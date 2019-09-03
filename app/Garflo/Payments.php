<?php

namespace App\Garflo;

use Exception;

class Payments
{

    /**
     * The payu merchant ID.
     *
     * @var string
     */
    protected static $merchantId;

    /**
     * The payu API login.
     *
     * @var string
     */
    protected static $apiLogin;

    /**
     * The payu API Key.
     *
     * @var string
     */
    protected static $apiKey;

    /**
     * The payu Account ID.
     *
     * @var string
     */
    protected static $accountId;

    /**
     * The country where the payment is processed.
     *
     * @var string
     */
    protected static $country;

    /**
     * The current currency.
     *
     * @var string
     */
    protected static $currency;

    /**
     * The current currency symbol.
     *
     * @var string
     */
    protected static $currencySymbol = '$';

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Default validation rules (for testing purposes).
     *
     * @var array
     */
    protected static $rules;

    /**
     * The account testing state.
     *
     * @var bool
     */
    protected static $isTesting;

    /**
     * Token key
     *
     * @var string
     */
    protected static $tokenizer;

    /**
     * Approved Response Code catalog
     *
     * @var array
     */
    public static $approvedResponseCode = array(
        'APPROVED' => 'Transacción aprobada',
    );

    /**
     * Declined Response Code catalog
     *
     * @var array
     */
    public static $declinedResponseCode = array(
        'PAYMENT_NETWORK_REJECTED' => 'Transacción rechazada por entidad financiera',
        'ENTITY_DECLINED' => 'Transacción rechazada por el banco',
        'INSUFFICIENT_FUNDS' => 'Fondos insuficientes',
        'INVALID_CARD' => 'Tarjeta inválida',
        'CONTACT_THE_ENTITY' => 'Contactar entidad financiera',
        'BANK_ACCOUNT_ACTIVATION_ERROR' => 'Débito automático no permitido',
        'BANK_ACCOUNT_NOT_AUTHORIZED_FOR_AUTOMATIC_DEBIT' => 'Débito automático no permitido',
        'INVALID_AGENCY_BANK_ACCOUNT' => 'Débito automático no permitido',
        'INVALID_BANK_ACCOUNT' => 'Débito automático no permitido',
        'INVALID_BANK' => 'Débito automático no permitido',
        'EXPIRED_CARD' => 'Tarjeta vencida',
        'RESTRICTED_CARD' => 'Tarjeta restringida',
        'INVALID_EXPIRATION_DATE_OR_SECURITY_CODE' => 'Fecha de expiración o código de seguridadinválidos',
        'REPEAT_TRANSACTION' => 'Reintentar pago',
        'INVALID_TRANSACTION' => 'Transacción inválida',
        'EXCEEDED_AMOUNT' => 'El valor excede el máximo permitido por la entidad',
        'ABANDONED_TRANSACTION' => 'Transacción abandonada por el pagador',
        'CREDIT_CARD_NOT_AUTHORIZED_FOR_INTERNET_TRANSACTIONS' => 'Tarjeta no autorizada para comprar por internet',
        'ANTIFRAUD_REJECTED' => 'Transacción rechazada por sospecha de fraude',
        'DIGITAL_CERTIFICATE_NOT_FOUND' => 'Certificado digital no encotnrado',
        'BANK_UNREACHABLE' => 'Error tratando de cominicarse con el banco',
        'ENTITY_MESSAGING_ERROR' => 'Error comunicándose con la entidad financiera',
        'NOT_ACCEPTED_TRANSACTION' => 'Transacción no permitida al tarjetahabiente',
        'INTERNAL_PAYMENT_PROVIDER_ERROR' => 'Error',
        'INACTIVE_PAYMENT_PROVIDER' => 'Error',
    );

    /**
     * Declined Response Code catalog
     *
     * @var array
     */
    public static $errorResponseCode = array(
        'ERROR' => 'Error',
        'ERROR_CONVERTING_TRANSACTION_AMOUNTS' => 'Error',
        'BANK_ACCOUNT_ACTIVATION_ERROR' => 'Error',
        'FIX_NOT_REQUIRED' => 'Error',
        'AUTOMATICALLY_FIXED_AND_SUCCESS_REVERSAL' => 'Error',
        'AUTOMATICALLY_FIXED_AND_UNSUCCESS_REVERSAL' => 'Error',
        'AUTOMATIC_FIXED_NOT_SUPPORTED' => 'Error',
        'NOT_FIXED_FOR_ERROR_STATE' => 'Error',
        'ERROR_FIXING_AND_REVERSING' => 'Error',
        'ERROR_FIXING_INCOMPLETE_DATA' => 'Error',
        'PAYMENT_NETWORK_BAD_RESPONSE' => 'Error',
        'PAYMENT_NETWORK_NO_CONNECTION' => 'No fue posible establecer comunicación con la entidad financiera',
        'PAYMENT_NETWORK_NO_RESPONSE' => 'No se recibió respuesta de la entidad financiera',
    );

    /**
     * Expired Response Code catalog
     *
     * @var array
     */
    public static $expiredResponseCode = array(
        'EXPIRED_TRANSACTION' => 'Transacción expirada',
    );

    /**
     * Pending Response Code catalog
     *
     * @var array
     */
    public static $pendingResponseCode = array(
        'PENDING_TRANSACTION_REVIEW' => 'Transacción en validación manual',
        'PENDING_TRANSACTION_CONFIRMATION' => 'Recibo de pago generado. En espera de pago',
        'PENDING_TRANSACTION_TRANSMISSION' => 'Transacción no permitida',
        'PENDING_PAYMENT_IN_ENTITY' => 'Recibo de pago generado. En espera de pago',
        'PENDING_PAYMENT_IN_BANK' => 'Recibo de pago generado. En espera de pago',
        'PENDING_SENT_TO_FINANCIAL_ENTITY' => 'Pendiente de enviar pago a la entidad financiera',
        'PENDING_AWAITING_PSE_CONFIRMATION' => 'En espera de confirmación de PSE',
        'PENDING_NOTIFYING_ENTITY' => 'Recibo de pago generado. En espera de pago',
    );

    /**
     * Pol Transaction State Approved
     *
     * @var integer
     */
    public static $statePolApproved = 4;

    /**
     * Pol Transaction State Expired
     *
     * @var integer
     */
    public static $statePolExpired = 5;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolDeclined = 6;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolPending = 7;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolPendingSent = 10;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolPendingAwaiting = 12;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolPendingPaymentEntity = 14;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolPendingPaymentBank = 15;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolPendingNotifying = 18;

    /**
     * Pol Transaction State Declined
     *
     * @var integer
     */
    public static $statePolError = 104;

    /**
     * Set the currency to be used when billing users.
     *
     * @param string $currency
     * @param string|null $symbol
     * @return void
     */
    public static function useCurrency($currency, $symbol = null)
    {
        static::$currency = $currency;
        static::useCurrencySymbol($symbol ?: static::guessCurrencySymbol($currency));
    }

    /**
     * Guess the currency symbol for the given currency.
     *
     * @param string $currency
     * @return string
     */
    protected static function guessCurrencySymbol($currency)
    {
        switch (strtolower($currency)) {
            case 'usd':
                return '$';
            case 'clp':
                return '$';
            case 'cop':
                return '$';
            case 'mxn':
                return '$';
            default:
                throw new Exception('Unable to guess symbol for currency. Please explicitly specify it.');
        }
    }

    /**
     * Get the currency currently use.
     *
     * @return string
     */
    public static function usesCurrency()
    {
        return static::$currency;
    }

    /**
     * Set the custom currency formatter.
     *
     * @param string $symbol
     * @return void
     */
    public static function useCurrencySymbol($symbol)
    {
        static::$currencySymbol = $symbol;
    }

    /**
     * Set the custom currency formater.
     *
     * @param callable $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param int $amount
     * @return string
     */
    public static function formatAmount($amount)
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount);
        }

        $amount = number_format($amount / 100, 2);

        if (starts_with($amount, '-')) {
            return '-' . static::usesCurrencySymbol() . ltrim($amount, '-');
        }

        return static::usesCurrencySymbol() . $amount;
    }

    /**
     * Get the Account ID.
     *
     * @return string
     */
    public static function getAccountId()
    {
        if (static::$accountId) {
            return static::$accountId;
        }

        if ($accountId = getenv('PAYU_ACCOUNT_ID')) {
            return $accountId;
        }

        return config('payu.payu_account');
    }

    /**
     * Get the Merchant IDre.
     *
     * @return string
     */
    public static function getMerchantId()
    {
        if (static::$merchantId) {
            return static::$merchantId;
        }

        if ($merchantId = getenv('PAYU_MERCHANT_ID')) {
            return $merchantId;
        }

        return config('payu.payu_merchant');
    }

    /**
     * Get the API Key.
     *
     * @return string
     */
    public static function getApiKey()
    {
        if (static::$apiKey) {
            return static::$apiKey;
        }

        if ($apiKey = getenv('PAYU_API_KEY')) {
            return $apiKey;
        }

        return config('payu.payu_key');
    }

    /**
     * Get the API Login
     *
     * @return string
     */
    public static function getApiLogin()
    {
        if (static::$apiLogin) {
            return static::$apiLogin;
        }

        if ($apiLogin = getenv('PAYU_API_LOGIN')) {
            return $apiLogin;
        }

        return config('payu.payu_key');
    }

    /**
     * Get the Account country
     *
     * @return string
     */
    public static function getCountry()
    {
        if (static::$country) {
            return static::$country;
        }

        if ($country = getenv('PAYU_COUNTRY')) {
            return $country;
        }

        return config('payu.payu_country');
    }

    /**
     * Get the PSE redirect url
     *
     * @return string
     */
    public static function getRedirectPSE()
    {
        if ($pseRedirect = getenv('PSE_REDIRECT_URL')) {
            return $pseRedirect;
        }

        return config('payu.pse_redirect');
    }

    /**
     * Set the Account testing state (never use on production)
     *
     * @param bool $state
     * @return string
     */
    public static function setAccountOnTesting($state)
    {
        static::$isTesting = $state;
    }

    /**
     * Get the account testing value.
     *
     * @return boolean
     */
    private static function isAccountInTesting()
    {
        if (!is_null(static::$isTesting)) {
            return static::$isTesting;
        }

        if ($isTesting = getenv('PAYU_ON_TESTING')) {
            return $isTesting;
        }

        return config('payu.payu_test');
    }

    private static function isAppInTesting()
    {
        if ($isLocal = getenv('APP_ENV')) {
            return $isLocal;
        }

        return config('app.env');
    }

    public static function getTokenizer()
    {
        if (static::$tokenizer) {
            return static::$tokenizer;
        }

        if ($tokenizer = getenv('APP_KEY')) {
            return $tokenizer;
        }

        return config('app.key');
    }

    /**
     * Check if PayU platform available.
     *
     * @return PayU RequestPaymentUtil
     */
    public static function doPing($onSuccess, $onError)
    {
        static::setPayUEnvironment();

        try {
            $response = \PayUPayments::doPing();
            if ($response) {
                $onSuccess($response);
            }
        } catch (\PayUException $exc) {
            $onError($exc);
        }
    }

    /**
     * Get array of available PSE Banks.
     *
     * @return array
     */
    public static function getPSEBanks($onSuccess, $onError)
    {
        static::setPayUEnvironment();

        try {
            $params[\PayUParameters::PAYMENT_METHOD] = 'PSE';
            $params[\PayUParameters::COUNTRY] = static::getCountry();

            $array = \PayUPayments::getPSEBanks($params);

            if ($array) {
                $onSuccess($array->banks);
            }
        } catch (\PayUException $exc) {
            $onError($exc);
        } catch (ConnectionException $exc) {
            $onError($exc);
        } catch (RuntimeException $exc) {
            $onError($exc);
        } catch (InvalidArgumentException $exc) {
            $onError($exc);
        }
    }

    /**
     * Set PayU Environment for the account.
     *
     * @return void
     */
    public static function setPayUEnvironment()
    {
        \PayU::$apiKey = static::getApiKey();
        \PayU::$apiLogin = static::getApiLogin();
        \PayU::$merchantId = static::getMerchantId();
        \Payu::$isTest = static::isAccountInTesting();

        if (static::isAppInTesting() == 'local') {
            \Environment::setPaymentsCustomUrl('https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi');
            \Environment::setReportsCustomUrl('https://sandbox.api.payulatam.com/reports-api/4.0/service.cgi');
            \Environment::setSubscriptionsCustomUrl('https://sandbox.api.payulatam.com/payments-api/rest/v4.9/');
        } else {
            \Environment::setPaymentsCustomUrl('https://api.payulatam.com/payments-api/4.0/service.cgi');
            \Environment::setReportsCustomUrl('https://api.payulatam.com/reports-api/4.0/service.cgi');
            \Environment::setSubscriptionsCustomUrl('https://api.payulatam.com/payments-api/rest/v4.9/');
        }
    }
}
