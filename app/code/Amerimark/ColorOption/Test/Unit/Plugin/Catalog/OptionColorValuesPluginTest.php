<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Plugin\Catalog;

use Amerimark\ColorOption\Model\OptionColors;
use Amerimark\ColorOption\Plugin\Catalog\OptionColorValuesPlugin;
use Magento\Catalog\Model\Product\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OptionColorValuesPluginTest extends TestCase
{
    private OptionColors&MockObject $colors;
    private OptionColorValuesPlugin $plugin;

    protected function setUp(): void
    {
        $this->colors = $this->createMock(OptionColors::class);
        $this->plugin = new OptionColorValuesPlugin($this->colors);
    }

    private function option(array $data): Option
    {
        $option = $this->createPartialMock(Option::class, ['getId']);
        $option->method('getId')->willReturn($data['option_id'] ?? null);
        $option->setData($data);
        return $option;
    }

    public function testRowsBecomeSelectValuesTitledWithTheColorName(): void
    {
        $this->colors->method('getColors')->willReturn([
            3 => ['item_id' => 3, 'name' => 'Red'],
            4 => ['item_id' => 4, 'name' => 'Navy'],
        ]);
        $option = $this->option(['type' => 'color_swatch', 'color_values' => [
            ['color_id' => '3', 'price' => '2.5', 'price_type' => 'percent', 'sku' => 'R', 'sort_order' => '1'],
            ['color_id' => '4', 'price' => '', 'price_type' => '', 'option_type_id' => '77'],
        ]]);

        $this->plugin->beforeValidateBeforeSave($option);

        $values = $option->getData('values');
        $this->assertCount(2, $values);
        $this->assertSame('Red', $values[0]['title']);
        $this->assertSame(3, $values[0]['color_id']);
        $this->assertSame('percent', $values[0]['price_type']);
        $this->assertSame(1, $values[0]['sort_order']);
        $this->assertArrayNotHasKey('option_type_id', $values[0], 'new rows get their id from the database');
        $this->assertSame('Navy', $values[1]['title']);
        $this->assertSame('fixed', $values[1]['price_type'], 'price type defaults like a drop-down value');
        $this->assertSame(77, $values[1]['option_type_id']);
    }

    public function testDeletedRowsKeepOnlyTheirId(): void
    {
        $this->colors->method('getColors')->willReturn([]);
        $option = $this->option(['type' => 'color_swatch', 'color_values' => [
            ['color_id' => '3', 'option_type_id' => '5', 'is_delete' => '1'],
            ['color_id' => '3', 'is_delete' => '1'],
        ]]);

        $this->plugin->beforeValidateBeforeSave($option);

        $this->assertSame([['option_type_id' => 5, 'is_delete' => 1]], $option->getData('values'));
    }

    public function testValuesTheFormNoLongerListsAreRemoved(): void
    {
        $this->colors->method('getColors')->willReturn([3 => ['item_id' => 3, 'name' => 'Red']]);
        $this->colors->method('getValueIdsOfOption')->with(9)->willReturn([10, 11]);
        $option = $this->option(['option_id' => 9, 'type' => 'color_swatch', 'color_values' => [
            ['color_id' => '3', 'option_type_id' => '10'],
        ]]);

        $this->plugin->beforeValidateBeforeSave($option);

        $values = $option->getData('values');
        $this->assertCount(2, $values);
        $this->assertSame(['option_type_id' => 11, 'is_delete' => 1], $values[1]);
    }

    public function testStoreViewSaveNeverCreatesStoreTitles(): void
    {
        $this->colors->method('getColors')->willReturn([3 => ['item_id' => 3, 'name' => 'Red']]);
        $option = $this->option(['type' => 'color_swatch', 'store_id' => 2, 'color_values' => [['color_id' => '3']]]);

        $this->plugin->beforeValidateBeforeSave($option);

        $this->assertSame(1, $option->getData('values')[0]['is_use_default']);
    }

    public function testChangedRowsOnTheSameObjectAreRecomputed(): void
    {
        $this->colors->method('getColors')->willReturn([3 => ['item_id' => 3, 'name' => 'Red'], 4 => ['item_id' => 4, 'name' => 'Navy']]);
        $option = $this->option(['type' => 'color_swatch', 'color_values' => [['color_id' => '3']]]);
        $this->plugin->beforeValidateBeforeSave($option);
        $this->plugin->beforeBeforeSave($option);
        $this->assertSame('Red', $option->getData('values')[0]['title']);

        $option->setData('color_values', [['color_id' => '4']]);
        $this->plugin->beforeValidateBeforeSave($option);
        $this->assertSame('Navy', $option->getData('values')[0]['title']);
    }

    public function testOtherTypesDropTheGroupAndForgetMappings(): void
    {
        $this->colors->expects($this->once())->method('deleteMappingsOfOption')->with(8);
        $option = $this->option(['option_id' => 8, 'type' => 'drop_down', 'color_group_id' => '']);

        $this->plugin->beforeBeforeSave($option);

        $this->assertNull($option->getData('color_group_id'));
        $this->assertNull($option->getData('values'));
    }

    public function testNewNonColorOptionsTouchNothingInTheDatabase(): void
    {
        $this->colors->expects($this->never())->method('deleteMappingsOfOption');
        $option = $this->option(['type' => 'text', 'color_group_id' => '']);

        $this->plugin->beforeBeforeSave($option);

        $this->assertNull($option->getData('color_group_id'));
    }

    public function testColorOptionKeepsItsGroupAndNeverStoresZero(): void
    {
        $this->colors->method('getColors')->willReturn([]);
        $option = $this->option(['type' => 'color_swatch', 'color_group_id' => '12', 'color_values' => []]);
        $this->plugin->beforeBeforeSave($option);
        $this->assertSame(12, $option->getData('color_group_id'));

        $none = $this->option(['type' => 'color_swatch', 'color_group_id' => '0', 'color_values' => []]);
        $this->plugin->beforeBeforeSave($none);
        $this->assertNull($none->getData('color_group_id'));
    }
}
