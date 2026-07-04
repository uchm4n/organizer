<?php

namespace App\Data\Api;

use App\Models\User;
use DateTimeInterface;
use Spatie\LaravelData\Data;

final class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?DateTimeInterface $email_verified_at,
        public ?DateTimeInterface $created_at,
        public ?DateTimeInterface $updated_at,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->getKey(),
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at,
            created_at: $user->created_at,
            updated_at: $user->updated_at,
        );
    }
}
