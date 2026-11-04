<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Model;

use Amerimark\ColorOption\Model\SwatchStyles;
use PHPUnit\Framework\TestCase;

class SwatchStylesTest extends TestCase
{
    private function styles(): SwatchStyles
    {
        return new SwatchStyles([
            'spectrum' => ['label' => 'Full spectrum', 'image' => 'conic-gradient(red, blue)'],
            'plain2' => ['label' => 'No image'],
        ]);
    }

    public function testSolidIsAlwaysFirstAndListedWithTheConfiguredStyles(): void
    {
        $this->assertSame(['solid', 'spectrum', 'plain2'], array_column($this->styles()->toOptionArray(), 'value'));
        $this->assertSame(['solid'], array_column((new SwatchStyles())->toOptionArray(), 'value'));
    }

    public function testUnknownOrEmptyStylesBecomeSolid(): void
    {
        $styles = $this->styles();

        $this->assertSame('spectrum', $styles->normalize('spectrum'));
        $this->assertSame('spectrum', $styles->normalize(' spectrum '));
        $this->assertSame('solid', $styles->normalize('rainbow'));
        $this->assertSame('solid', $styles->normalize(''));
        $this->assertSame('solid', $styles->normalize(null));
        $this->assertSame('solid', $styles->normalize(['spectrum']));
    }

    public function testInlineCssPaintsTheColorThenTheImageOverIt(): void
    {
        $styles = $this->styles();

        $this->assertSame('background-color:#0000FF', $styles->getInlineCss('solid', '#0000FF'));
        $this->assertSame(
            'background-color:#888888;background-image:conic-gradient(red, blue)',
            $styles->getInlineCss('spectrum', '#888888')
        );
        $this->assertSame('background-color:#123456', $styles->getInlineCss('plain2', '#123456'));
        $this->assertSame('background-color:#123456', $styles->getInlineCss('nonsense', '#123456'));
    }

    public function testImageMapListsEveryStyle(): void
    {
        $this->assertSame(
            ['solid' => '', 'spectrum' => 'conic-gradient(red, blue)', 'plain2' => ''],
            $this->styles()->getImageMap()
        );
    }
}
