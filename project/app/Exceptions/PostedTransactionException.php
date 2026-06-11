<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class PostedTransactionException extends RuntimeException
{
    public static function modify(): self
    {
        return new self("Нельзя изменять проведённую транзакцию.");
    }

    public static function delete(): self
    {
        return new self("Нельзя удалить проведённую транзакцию.");
    }
}
