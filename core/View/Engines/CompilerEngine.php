<?php

declare(strict_types=1);

namespace Plugs\View\Engines;

use Plugs\View\Engines\PhpEngine;
use Plugs\View\Contracts\CompilerInterface;


class CompilerEngine extends PhpEngine
{
    protected CompilerInterface $compiler;

    public function __construct(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    public function get(string $path, array $data = []): string
    {
        $compiled = $this->compiler->getCompiledPath($path);

        if ($this->compiler->isExpired($path, $compiled)) {
            $this->compiler->compile($path);
        }

        return parent::get($compiled, $data);
    }

    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }
}