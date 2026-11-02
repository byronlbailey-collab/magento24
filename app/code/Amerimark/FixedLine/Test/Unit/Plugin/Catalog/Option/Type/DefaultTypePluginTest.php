<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Test\Unit\Plugin\Catalog\Option\Type;

use Amerimark\FixedLine\Plugin\Catalog\Option\Type\DefaultTypePlugin;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\DefaultType;
use Magento\Catalog\Model\Product\Option\Value;
use PHPUnit\Framework\TestCase;

class DefaultTypePluginTest extends TestCase
{
    private function proceed(array &$calls): callable
    {
        return function ($value, $base) use (&$calls) {
            $calls[] = $value;
            return 7.0;
        };
    }

    private function subject(Option $option): DefaultType
    {
        $subject = $this->createMock(DefaultType::class);
        $subject->method('getOption')->willReturn($option);
        return $subject;
    }

    private function value(string $type): Value
    {
        $value = $this->createMock(Value::class);
        $value->method('getPriceType')->willReturn($type);
        return $value;
    }

    public function testNonSelectFixedLineContributesZero(): void
    {
        $option = $this->createMock(Option::class);
        $option->method('getGroupByType')->willReturn(Option::OPTION_GROUP_TEXT);
        $option->method('getPriceType')->willReturn('fxline');
        $calls = [];
        $result = (new DefaultTypePlugin())->aroundGetOptionPrice($this->subject($option), $this->proceed($calls), 'x', 1.0);
        $this->assertSame(0.0, $result);
        $this->assertSame([], $calls);
    }

    public function testNonSelectFixedIsUntouched(): void
    {
        $option = $this->createMock(Option::class);
        $option->method('getGroupByType')->willReturn(Option::OPTION_GROUP_TEXT);
        $option->method('getPriceType')->willReturn('fixed');
        $calls = [];
        $result = (new DefaultTypePlugin())->aroundGetOptionPrice($this->subject($option), $this->proceed($calls), 'x', 1.0);
        $this->assertSame(7.0, $result);
    }

    public function testSelectDropsOnlyFixedLineValues(): void
    {
        $option = $this->createMock(Option::class);
        $option->method('getGroupByType')->willReturn(Option::OPTION_GROUP_SELECT);
        $option->method('getValueById')->willReturnMap([
            ['1', $this->value('fxline')],
            ['2', $this->value('fixed')],
            ['3', $this->value('percent')],
        ]);
        $calls = [];
        $result = (new DefaultTypePlugin())->aroundGetOptionPrice($this->subject($option), $this->proceed($calls), '1,2,3', 1.0);
        $this->assertSame(7.0, $result);
        $this->assertSame(['2,3'], $calls);
    }

    public function testSelectWithOnlyFixedLineContributesZeroWithoutProceeding(): void
    {
        $option = $this->createMock(Option::class);
        $option->method('getGroupByType')->willReturn(Option::OPTION_GROUP_SELECT);
        $option->method('getValueById')->willReturn($this->value('fxline'));
        $calls = [];
        $result = (new DefaultTypePlugin())->aroundGetOptionPrice($this->subject($option), $this->proceed($calls), '1', 1.0);
        $this->assertSame(0.0, $result);
        $this->assertSame([], $calls);
    }

    public function testSelectWithoutFixedLinePassesValueThroughUnchanged(): void
    {
        $option = $this->createMock(Option::class);
        $option->method('getGroupByType')->willReturn(Option::OPTION_GROUP_SELECT);
        $option->method('getValueById')->willReturn($this->value('fixed'));
        $calls = [];
        (new DefaultTypePlugin())->aroundGetOptionPrice($this->subject($option), $this->proceed($calls), '4,5', 1.0);
        $this->assertSame(['4,5'], $calls);
    }
}
