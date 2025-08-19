<?php

declare(strict_types=1);

namespace Illusion\Mailer\Contracts;

interface TemplateRendererInterface
{
    public function render(string $template, array $data = []): string;
}