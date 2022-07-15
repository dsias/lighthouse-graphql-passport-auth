<?php

namespace Joselfonseca\LighthouseGraphQLPassport\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Class UserDeleted.
 */
class UserDeleted
{
    /**
     * @var Authenticatable
     */
    public $user;

    /**
     * UserDeleted constructor.
     *
     * @param Authenticatable $user
     */
    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }
}
