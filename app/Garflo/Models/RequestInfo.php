<?php

namespace App\Garflo\Models;

use Illuminate\Database\Eloquent\Model;

class RequestInfo extends Model
{
    protected $table = 'request_info';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_system', 'id_payer', 'id_reservation', 'payment_method', 'manual_validation',
    ];

    /**
     * Relation with the model Systems. Cardinality One to Many
     *
     * @return void
     */
    public function systems()
    {
        return $this->belongsTo('App\Garflo\Models\System', 'id');
    }

    /**
     * Relation with the model Payment Info. Cardinality Many to One
     *
     * @return void
     */
    public function paymentInfo()
    {
        return $this->hasMany('App\Garflo\Models\PaymentInfo', 'id_request_info');
    }

    /**
     * Relation with the model Payer Data. Cardinality Many to One
     *
     * @return void
     */
    public function payerData()
    {
        return $this->hasMany('App\Garflo\Models\PayerData', 'id_request_info');
    }

    /**
     * Relation with the model Transaction Response. Cardinality Many to One
     *
     * @return void
     */
    public function transactionResponse()
    {
        return $this->hasMany(TransactionResponse::class, 'id_request_info');
    }

    /**
     * Scope to validate if the record exist
     *
     * @param string $reference
     * @return boolean
     */
    public static function scopeRequestExist($query, $reference)
    {
        if ($query->where('id_reservation', $reference)->count() > 0) {
            return true;
        }

        return false;
    }

    public static function scopegetId($query, $reference)
    {
        return $query->select('id')->where('id_reservation', $reference)->get();
    }
}
