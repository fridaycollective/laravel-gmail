<?php

namespace FridayCollective\LaravelGmail\Models;

use Illuminate\Database\Eloquent\Model;

class UserMailConfig extends Model
{
    protected $fillable = [
      "user_id",
      "email",
      "config",
      "type",
      "status",
    ];

    public function user(){
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
