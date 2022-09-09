<?php

namespace gammak\LighthouseGraphQLPassport;

use gammak\LighthouseGraphQLPassport\Notifications\VerifyEmail;

trait MustVerifyEmailGraphQL
{
    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail());
    }
}
