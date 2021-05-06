<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['name'];

    public function contactNumbers()
    {
        return $this->hasMany('App\ContactNumber', 'contact_id');
    }
}
