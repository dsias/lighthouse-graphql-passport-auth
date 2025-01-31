<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use gammak\LighthouseGraphQLPassport\Tests\User;

app(Factory::class)->define(\Joselfonseca\LighthouseGraphQLPassport\Models\SocialProvider::class, function (Faker $faker) {
    return [
        'user_id' => factory(User::class)->create()->getKey(),
        'provider' => 'github',
        'provider_id' => 'fakeId',
    ];
});
