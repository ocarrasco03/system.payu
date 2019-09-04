<?php

namespace App\Garflo\Models;

use Illuminate\Database\Eloquent\Model;

class PayerData extends Model
{

    protected $table = 'payer_data';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name', 'email', 'phone', 'address', 'city', 'state', 'country', 'zip_code',
    ];

    /**
     * Relation with the model Request Info. Cardinality One to Many
     *
     * @return void
     */
    public function requestInfo()
    {
        return $this->belongsTo('App\Garflo\Models\RequestInfo', 'id');
    }

    /**
     * Scope to validate if the record exist
     *
     * @param string $email
     * @return boolean
     */
    public static function scopePayerExist($query, $email)
    {
        if ($query->where('email', $email)->count() > 0) {
            return true;
        }
        
        return false;
    }

    public static function scopeGetId($query, $email)
    {
        return $query->select('id')->where([
            ['email', '=', $email],
        ])->get();
    }
}
