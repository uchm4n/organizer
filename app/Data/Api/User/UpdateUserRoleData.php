<?php

namespace App\Data\Api\User;

use App\Enums\Role;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * Request payload for `PATCH /users/{user}/role`.
 */
final class UpdateUserRoleData extends Data
{
    public function __construct(
        #[Required]
        #[Enum(Role::class)]
        public Role $role,
    ) {}
}
