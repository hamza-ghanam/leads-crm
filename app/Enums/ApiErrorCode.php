<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case ACCOUNT_BANNED = 'ACCOUNT_BANNED';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case SERVER_ERROR = 'SERVER_ERROR';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case RATE_LIMITED = 'RATE_LIMITED';
    case IDEMPOTENCY_KEY_REQUIRED = 'IDEMPOTENCY_KEY_REQUIRED';



    public function message(): string
    {
        return match ($this) {
            self::INVALID_CREDENTIALS => 'Invalid credentials',
            self::ACCOUNT_BANNED => 'Your account has been suspended',
            self::UNAUTHORIZED => 'Unauthorized access',
            self::VALIDATION_ERROR => 'The given data was invalid.',
            self::FORBIDDEN => 'You do not have permission to perform this action',
            self::NOT_FOUND => 'Resource not found',
            self::SERVER_ERROR => 'Something went wrong. Please try again later.',
            self::RATE_LIMITED => 'Too many requests. Please try again later.',
            self::IDEMPOTENCY_KEY_REQUIRED => 'Idempotency Key is required.',
        };
    }
}
