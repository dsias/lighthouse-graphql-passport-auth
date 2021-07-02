<?php

namespace Joselfonseca\LighthouseGraphQLPassport\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Joselfonseca\LighthouseGraphQLPassport\Events\UserLoggedIn;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Joselfonseca\LighthouseGraphQLPassport\Exceptions\AuthenticationException;

class Login extends BaseAuthResolver
{
    /**
     * @param $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     * @param \GraphQL\Type\Definition\ResolveInfo                     $resolveInfo
     *
     * @throws \Exception
     *
     * @return array
     */
    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {
        $credentials = $this->buildCredentials($args);
        $response = $this->makeRequest($credentials);
        $user = $this->findUser($args['username']);

        $this->validateUser($user);

        $check_2fa = $this->check2fa($user);

        if ($check_2fa) {
            $this->validate2fa($args['code'] ?? null, $args['recovery_code'] ?? null);
            $this->challenge2fa($user, $args['code'] ?? null, $args['recovery_code'] ?? null);
        }

        event(new UserLoggedIn($user));

        return array_merge(
            $response,
            [
                'user' => $user,
            ]
        );
    }

    protected function validateUser($user)
    {
        $authModelClass = $this->getAuthModelClass();
        if ($user instanceof $authModelClass && optional($user)->exists) {
            return;
        }

        throw (new ModelNotFoundException())
            ->setModel($authModelClass);
    }

    protected function check2fa($user)
    {
        if (!class_exists('\Laravel\Fortify\FortifyServiceProvider')) {
            return false;
        }
        if (! '\Laravel\Fortify\Features'::enabled('\Laravel\Fortify\Features'::twoFactorAuthentication())) {
            return false;
        }

        return optional($user)->two_factor_secret &&
            in_array(\Laravel\Fortify\TwoFactorAuthenticatable::class, class_uses_recursive($user));
    }

    protected function validate2fa($code, $recovery_code)
    {
        if ($recovery_code || $code) {
            return true;
        }
        throw new AuthenticationException(__('Failed two factor challenge'), __('Challenge required'));
    }

    protected function challenge2fa($user, $code, $recovery_code)
    {
        if (!class_exists('\Laravel\Fortify\FortifyServiceProvider')) {
            return false;
        }

        if ($recovery_code) {
            $new_code = collect($user->recoveryCodes())->first(function ($code) use ($recovery_code) {
                return hash_equals($recovery_code, $code) ? $code : null;
            });

            if ($new_code) {
                $user->replaceRecoveryCode($new_code);
                return true;
            } else {
                throw new AuthenticationException(__('Failed two factor challenge'), __('Invalid recovery code'));
            }
        }

        $valid = $code && app('\Laravel\Fortify\TwoFactorAuthenticationProvider')->verify(
            decrypt($user->two_factor_secret),
            $code
        );

        if ($valid) return $valid;

        throw new AuthenticationException(__('Failed two factor challenge'), __('Invalid two factor code'));
    }

    protected function findUser(string $username)
    {
        $model = $this->makeAuthModelInstance();

        if (method_exists($model, 'findForPassport')) {
            return $model->findForPassport($username);
        }

        return $model::query()
            ->where(config('lighthouse-graphql-passport.username'), $username)
            ->first();
    }
}
