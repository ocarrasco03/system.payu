<?php

namespace App\Garflo\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentInfo extends Model
{

    protected $table = 'payment_info';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_request_info', 'reference_code', 'description', 'value', 'currency', 'payment_method', 'expiration_date', 'tax_value', 'tax_return_base',
    ];

    /**
     * Relations to model Rquest Info
     *
     * @return void
     */
    public function requestInfo()
    {
        return $this->belongsTo('App\RequestInfo', 'id');
    }

    public function scopePaymentExist($query, $reqId)
    {
        if ($query->where('id_request_info', $reqId)->count() > 0) {
            return true;
        }

        return false;
    }
}
