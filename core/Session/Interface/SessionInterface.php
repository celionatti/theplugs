<?php

declare(strict_types=1);

namespace Plugs\Session\Interface;

interface SessionInterface
{
    public function start(): bool;
    public function put(string $key, mixed $value): void;
    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function forget(string|array $keys): void;
    public function all(): array;
    public function flash(string $key, mixed $value): void;
    public function reflash(): void;
    public function clear(): void;
    public function invalidate(): bool;
    public function regenerate(): bool;
    public function token(): string;
    public function getId(): ?string;
}