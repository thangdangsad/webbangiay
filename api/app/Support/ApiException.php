<?php

namespace App\Support;

use RuntimeException;

/** Lỗi nghiệp vụ trả về dạng { error: { code, message, details } } */
class ApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 400,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function unauthorized(string $code = 'UNAUTHENTICATED', string $message = 'Vui lòng đăng nhập'): self
    {
        return new self($code, $message, 401);
    }

    public static function forbidden(string $message = 'Bạn không có quyền thực hiện thao tác này'): self
    {
        return new self('FORBIDDEN', $message, 403);
    }

    public static function notFound(string $message = 'Không tìm thấy dữ liệu'): self
    {
        return new self('NOT_FOUND', $message, 404);
    }

    public static function conflict(string $code, string $message, array $details = []): self
    {
        return new self($code, $message, 409, $details);
    }

    public static function invalid(string $message, array $details = []): self
    {
        return new self('VALIDATION', $message, 422, $details);
    }
}
