<?php

namespace App\Garflo\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    protected $table = 'systems';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'label', 'status',
    ];

    /**
     * Relations to model Rquest Info
     *
     * @return void
     */
    public function requestInfo()
    {
        return $this->hasMany('App\Garflo\Models\RequestInfo', 'id_systems');
    }
}
