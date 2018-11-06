<?php

declare(strict_types=1);

namespace App\Data;

class UcrmUser
{
    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $username;

    /**
     * @var bool
     */
    public $isClient;

    /**
     * @var int|null
     */
    public $clientId;

    /**
     * @var string|null
     */
    public $userGroup;

    /**
     * @var string[]
     */
    public $permissions = [];

    /**
     * @var string[]
     */
    public $specialPermissions = [];

    public function __construct(array $data)
    {
        $this->userId = $data['userId'];
        $this->username = $data['username'];
        $this->isClient = $data['isClient'];
        $this->clientId = $data['clientId'];
        $this->userGroup = $data['userGroup'];
        $this->permissions = $data['permissions'] ?? [];
        $this->specialPermissions = $data['specialPermissions'] ?? [];
    }

    public function canView(string $permission): bool
    {
        return array_key_exists($permission, $this->permissions)
            && in_array($this->permissions[$permission], ['view', 'edit'], true);
    }

    public function canEdit(string $permission): bool
    {
        return array_key_exists($permission, $this->permissions)
            && $this->permissions[$permission] === 'edit';
    }

    public function hasSpecialPermission(string $specialPermission): bool
    {
        return array_key_exists($specialPermission, $this->specialPermissions)
            && $this->specialPermissions[$specialPermission] === 'allow';
    }
}
