<?php

namespace gammak\LighthouseGraphQLPassport\Models;

use Illuminate\Database\Eloquent\Model;
use gammak\LighthouseGraphQLPassport\Contracts\AuthModelFactory;

class SocialProvider extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_token',
    ];

    public function user()
    {
        return $this->belongsTo($this->getAuthModelFactory()->getClass());
    }

    protected function getAuthModelFactory(): AuthModelFactory
    {
        return app(AuthModelFactory::class);
    }
}
