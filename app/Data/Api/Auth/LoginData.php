<?php

namespace App\Data\Api\Auth;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Request payload for `POST /login`.
 */
final class LoginData extends Data
{
    public function __construct(
        #[Required]
        #[StringType]
        #[Email('rfc')]
        #[Max(255)]
        public string $email,

        #[Required]
        #[StringType]
        public string $password,
    ) {}
}
