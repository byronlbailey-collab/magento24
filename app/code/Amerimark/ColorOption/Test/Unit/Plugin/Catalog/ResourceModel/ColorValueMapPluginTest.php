<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Plugin\Catalog\ResourceModel;

use Amerimark\ColorOption\Model\OptionColors;
use Amerimark\ColorOption\Plugin\Catalog\ResourceModel\ColorValueMapPlugin;
use Magento\Catalog\Model\Product\Option\Value;
use Magento\Catalog\Model\ResourceModel\Product\Option\Value as ValueResource;
use PHPUnit\Framework\TestCase;

class ColorValueMapPluginTest extends TestCase
{
    private function value(array $data): Value
    {
        $value = $this->createPartialMock(Value::class, ['getId']);
        $value->method('getId')->willReturn($data['option_type_id'] ?? null);
        $value->setData($data);
        return $value;
    }

    public function testSavedColorValueIsMapped(): void
    {
        $colors = $this->createMock(OptionColors::class);
        $colors->expects($this->once())->method('saveMapping')->with(41, 6);
        $resource = $this->createMock(ValueResource::class);

        $result = (new ColorValueMapPlugin($colors))->afterSave(
            $resource,
            $resource,
            $this->value(['option_type_id' => 41, 'color_id' => '6'])
        );

        $this->assertSame($resource, $result);
    }

    public function testOrdinaryValuesAreIgnored(): void
    {
        $colors = $this->createMock(OptionColors::class);
        $colors->expects($this->never())->method('saveMapping');
        $resource = $this->createMock(ValueResource::class);

        (new ColorValueMapPlugin($colors))->afterSave($resource, $resource, $this->value(['option_type_id' => 41]));
    }
}
