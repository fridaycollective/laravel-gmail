<?php

namespace FridayCollective\LaravelGmail\Traits;

use FridayCollective\LaravelGmail\Models\UserMailConfig;

trait HasMailConfig
{
    public function mailConfig()
    {
        return $this->hasOne(UserMailConfig::class);
    }
}
