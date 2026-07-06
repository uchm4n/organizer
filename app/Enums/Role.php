<?php

namespace App\Enums;

/**
 * Roles assigned to application users.
 *
 * Unauthenticated requests are treated as "guests" — there is no `Guest`
 * case here by design. Every stored user is either an admin or a regular
 * user; finer-grained access control lives in Eloquent policies.
 */
enum Role: string
{
    case Admin = 'admin';
    case User  = 'user';

    /**
     * Human-readable label suitable for API display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::User  => 'User',
        };
    }

    /**
     * Whether this role grants administrative privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Whether this role is a regular user (non-admin).
     */
    public function isUser(): bool
    {
        return $this === self::User;
    }
}
