<?php

namespace gammak\LighthouseGraphQLPassport;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use gammak\LighthouseGraphQLPassport\Models\SocialProvider;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Crypt;

/**
 * Trait HasSocialLogin.
 */
trait HasSocialLogin
{
    public function socialProviders()
    {
        return $this->hasMany(SocialProvider::class);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public static function byOAuthToken(Request $request)
    {
        /** @var AbstractProvider */
        $provider = Socialite::driver($request->get('provider'));
        $userData = $provider->userFromToken($request->get('token'));

        try {
            $user = static::whereHas('socialProviders', function ($query) use ($request, $userData) {
                $query->where('provider', Str::lower($request->get('provider')))->where('provider_id', $userData->getId());
            })->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $user = static::where('email', $userData->getEmail())->first();
            if (! $user) {
                $user = static::create([
                    'name' => $userData->getName(),
                    'email' => $userData->getEmail(),
                    'uuid' => Str::uuid(),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                ]);
            }
            SocialProvider::create([
                'user_id' => $user->getKey(),
                'provider' => $request->get('provider'),
                'provider_id' => $userData->getId(),
                'provider_token' => Crypt::encryptString($request->get('token')),
            ]);
        }
        Auth::setUser($user);

        return $user;
    }
}
