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
        'id_request_info', 'id_order', 'id_transaction', 'status', 'response_code', 'pending_reason', 'url_payment_recipt_html', 'url_payment_recipt_pdf', 'global_update',
    ];

    /**
     * Relations to model Rquest Info
     *
     * @return void
     */
    public function requestInfo()
    {
        return $this->belongsTo(RequestInfo::class, 'id');
    }

    public static function scopegetTransactionStatusByRequest($query, $request)
    {
        return $query->where('id_request_info', $request)->get();
    }

    public static function scoperequestHasTransactions($query, $request)
    {
        if ($query->where('id_request_info', $request)->count() > 0) {
            return true;
        }

        return false;
    }

}
