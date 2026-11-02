<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Model;

/**
 * FixedLine option price type: a flat amount charged once per cart line, regardless of quantity.
 *
 * The code must fit the varchar(7) price_type column in core tables.
 */
final class PriceType
{
    public const CODE = 'fxline';

    /**
     * Check whether a price type code is FixedLine.
     *
     * @param string|null $priceType
     * @return bool
     */
    public static function isFixedLine(?string $priceType): bool
    {
        return $priceType === self::CODE;
    }
}
