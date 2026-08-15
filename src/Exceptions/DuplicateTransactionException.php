<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

class DuplicateTransactionException extends StagingException
{
    public static function forSource(string $source, string $transactionId): self
    {
        return new self(sprintf('Transaction %s already staged from source %s', $transactionId, $source));
    }

    public static function forPayment(string $paymentId): self
    {
        return new self(sprintf('Payment %s has already been staged', $paymentId));
    }
}
