<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Plugin\Catalog\Block;

use Amerimark\ColorOption\Plugin\Catalog\Block\OptionsGroupPlugin;
use Magento\Catalog\Block\Product\View\Options;
use PHPUnit\Framework\TestCase;

class OptionsGroupPluginTest extends TestCase
{
    public function testColorOptionsGetTheirOwnRenderer(): void
    {
        $subject = $this->createMock(Options::class);
        $plugin = new OptionsGroupPlugin();

        $this->assertSame('color_swatch', $plugin->afterGetGroupOfOption($subject, 'select', 'color_swatch'));
        $this->assertSame('select', $plugin->afterGetGroupOfOption($subject, 'select', 'drop_down'));
        $this->assertSame('text', $plugin->afterGetGroupOfOption($subject, 'text', 'field'));
    }
}
