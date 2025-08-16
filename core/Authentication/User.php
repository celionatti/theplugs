<?php

declare(strict_types=1);

namespace Plugs\Authentication;

use PDO;
use DateTime;
use Plugs\Authentication\Interface\UserInterface;

class User implements UserInterface
{
    private array $data;
    private PDO $db;
    private ?array $roles = null;
    private ?array $permissions = null;

    public function __construct(array $data, PDO $db)
    {
        $this->data = $data;
        $this->db = $db;
    }

    public function getId(): int
    {
        return (int)$this->data['id'];
    }

    public function getEmail(): string
    {
        return $this->data['email'];
    }

    public function getPassword(): string
    {
        return $this->data['password'];
    }

    public function getName(): ?string
    {
        return $this->data['name'];
    }

    public function isEmailVerified(): bool
    {
        return !empty($this->data['email_verified_at']);
    }

    public function has2FAEnabled(): bool
    {
        return !empty($this->data['two_factor_secret']);
    }

    public function get2FASecret(): ?string
    {
        return $this->data['two_factor_secret'];
    }

    public function getBackupCodes(): ?string
    {
        return $this->data['backup_codes'];
    }

    public function getFailedAttempts(): int
    {
        return (int)$this->data['failed_attempts'];
    }

    public function isLocked(): bool
    {
        if (empty($this->data['locked_until'])) {
            return false;
        }
        
        return new DateTime($this->data['locked_until']) > new DateTime();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions(), true);
    }

    public function getRoles(): array
    {
        if ($this->roles === null) {
            $stmt = $this->db->prepare("
                SELECT r.name 
                FROM roles r 
                JOIN user_roles ur ON r.id = ur.role_id 
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$this->getId()]);
            $this->roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return $this->roles;
    }

    public function getPermissions(): array
    {
        if ($this->permissions === null) {
            $stmt = $this->db->prepare("
                SELECT DISTINCT p.name 
                FROM permissions p 
                JOIN role_permissions rp ON p.id = rp.permission_id 
                JOIN user_roles ur ON rp.role_id = ur.role_id 
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$this->getId()]);
            $this->permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        return $this->permissions;
    }
}