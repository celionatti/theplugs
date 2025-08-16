<?php

declare(strict_types=1);

namespace Plugs\Authentication\Interface;

interface UserInterface
{
    public function getId(): int;
    public function getEmail(): string;
    public function getPassword(): string;
    public function getName(): ?string;
    public function isEmailVerified(): bool;
    public function has2FAEnabled(): bool;
    public function get2FASecret(): ?string;
    public function getBackupCodes(): ?string;
    public function getFailedAttempts(): int;
    public function isLocked(): bool;
    public function hasRole(string $role): bool;
    public function hasPermission(string $permission): bool;
    public function getRoles(): array;
    public function getPermissions(): array;
}