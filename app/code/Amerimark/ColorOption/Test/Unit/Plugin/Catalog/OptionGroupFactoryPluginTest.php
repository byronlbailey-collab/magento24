<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Plugin\Catalog;

use Amerimark\ColorOption\Model\Product\Option\Type\ColorSwatch;
use Amerimark\ColorOption\Model\Product\Option\Type\ColorSwatchFactory;
use Amerimark\ColorOption\Plugin\Catalog\OptionGroupFactoryPlugin;
use Magento\Catalog\Model\Product\Option;
use PHPUnit\Framework\TestCase;

class OptionGroupFactoryPluginTest extends TestCase
{
    public function testColorOptionsUseTheColorTypeClass(): void
    {
        $swatch = $this->createMock(ColorSwatch::class);
        $factory = $this->createMock(ColorSwatchFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($swatch);
        $proceed = function () {
            $this->fail('the core factory must not be asked for a color option');
        };

        $result = (new OptionGroupFactoryPlugin($factory))
            ->aroundGroupFactory($this->createMock(Option::class), $proceed, 'color_swatch');

        $this->assertSame($swatch, $result);
    }

    public function testOtherTypesGoToTheCoreFactory(): void
    {
        $factory = $this->createMock(ColorSwatchFactory::class);
        $factory->expects($this->never())->method('create');
        $core = new \stdClass();

        $result = (new OptionGroupFactoryPlugin($factory))
            ->aroundGroupFactory($this->createMock(Option::class), fn ($type) => $type === 'drop_down' ? $core : null, 'drop_down');

        $this->assertSame($core, $result);
    }
}
