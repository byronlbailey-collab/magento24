<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model;

/**
 * Normalizes display colors to #RRGGBB.
 */
final class HexColor
{
    /**
     * Accepts "#c0ffee", "C0FFEE", "#abc" (shorthand); returns "#C0FFEE" or null when it is not a color.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function normalize($value): ?string
    {
        $hex = ltrim(trim((string)$value), '#');
        if (preg_match('/^[0-9a-f]{3}$/i', $hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return preg_match('/^[0-9a-f]{6}$/i', $hex) ? '#' . strtoupper($hex) : null;
    }
}
