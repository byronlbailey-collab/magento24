<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Model;

use Amerimark\ColorOption\Model\HexColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HexColorTest extends TestCase
{
    public static function colors(): array
    {
        return [
            'full with hash' => ['#c0ffee', '#C0FFEE'],
            'full without hash' => ['C0FFEE', '#C0FFEE'],
            'shorthand' => ['#abc', '#AABBCC'],
            'shorthand without hash' => ['f0a', '#FF00AA'],
            'padded' => ['  #00ff00 ', '#00FF00'],
            'empty' => ['', null],
            'too short' => ['#ab', null],
            'wrong length' => ['#abcd', null],
            'not hex' => ['#GGGGGG', null],
            'rgb text' => ['rgb(1,2,3)', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('colors')]
    public function testNormalize(mixed $input, ?string $expected): void
    {
        $this->assertSame($expected, HexColor::normalize($input));
    }
}
