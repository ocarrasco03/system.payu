<?php

namespace App\Garflo\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionResponse extends Model
{
    protected $table = 'transaction_response';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_request_info', 'id_order', 'id_transaction', 'status', 'response_code', 'pending_reason', 'url_payment_recipt_html',
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

}
