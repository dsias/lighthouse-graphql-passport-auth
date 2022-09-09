<?php

namespace gammak\LighthouseGraphQLPassport\Tests\Http\Middleware;

use gammak\LighthouseGraphQLPassport\Tests\TestCase;
use gammak\LighthouseGraphQLPassport\Tests\User;

class AuthenticateWithApiGuard extends TestCase
{
    public function test_it_sets_user_via_global_middleware()
    {
        $this->createClient();
        $user = factory(User::class)->create();
        $response = $this->postGraphQL([
            'query' => 'mutation {
                login(input: {
                    username: "jose@example.com",
                    password: "123456789qq"
                }) {
                    access_token
                    refresh_token
                    user {
                        id
                        name
                        email
                    }
                }
            }',
        ]);
        $responseBody = json_decode($response->getContent(), true);
        $access_token = $responseBody['data']['login']['access_token'];
        $response = $this->postGraphQL([
            'query' => '{
                loggedInUserViaGuardForTest {
                    id
                    name
                    email
                }
            }',
        ], [
            'Authorization' => 'Bearer '.$access_token,
        ]);
        $response->assertJson([
            'data' => [
                'loggedInUserViaGuardForTest' => [
                    'id'    => $user->getKey(),
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }
}
