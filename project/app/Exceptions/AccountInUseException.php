<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AccountInUseException extends RuntimeException
{
    public static function delete(): self
    {
        return new self(
            'Нельзя удалить счёт: по нему есть проводки. Деактивируйте счёт или удалите связанные транзакции.',
        );
    }
}
