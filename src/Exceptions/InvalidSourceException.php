<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Exceptions;

class InvalidSourceException extends StagingException
{
    public static function unknownSource(string $source): self
    {
        return new self(sprintf('Unknown source: %s. Valid sources: woocommerce, square_api, square_csv, paypal, bank', $source));
    }

    public static function emptySource(): self
    {
        return new self('Source must not be empty');
    }
}
