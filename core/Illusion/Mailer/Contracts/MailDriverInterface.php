<?php

declare(strict_types=1);

namespace Illusion\Mailer\Contracts;

use Illusion\Mailer\MailEnvelope;

interface MailDriverInterface
{
    public function send(MailEnvelope $envelope): bool;
}