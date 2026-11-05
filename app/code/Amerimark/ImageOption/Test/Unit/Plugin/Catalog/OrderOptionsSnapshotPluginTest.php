<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Plugin\Catalog;

use Amerimark\ImageOption\Model\GroupItems;
use Amerimark\ImageOption\Plugin\Catalog\OrderOptionsSnapshotPlugin;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Type\AbstractType;
use PHPUnit\Framework\TestCase;

class OrderOptionsSnapshotPluginTest extends TestCase
{
    private function plugin(?array $item): OrderOptionsSnapshotPlugin
    {
        $groupItems = $this->createMock(GroupItems::class);
        $groupItems->method('findItem')->willReturn($item);
        return new OrderOptionsSnapshotPlugin($groupItems);
    }

    private function product(): Product
    {
        $option = $this->createPartialMock(Option::class, []);
        $option->setData(['option_id' => 11, 'image_group_id' => 14]);
        $product = $this->createMock(Product::class);
        $product->method('getOptionById')->willReturn($option);
        return $product;
    }

    private function entry(string $type, string $value): array
    {
        return ['option_id' => 11, 'option_type' => $type, 'option_value' => $value, 'label' => 'Imprint Logo'];
    }

    public function testChosenImageGetsAnOrderTimeSnapshot(): void
    {
        $item = ['item_id' => 28, 'label' => 'Smiley', 'sku' => 'LOGO-SMILEY', 'image' => 'smiley.png'];
        $result = $this->plugin($item)->afterGetOrderOptions(
            $this->createMock(AbstractType::class),
            ['options' => [$this->entry('image_group', '28')]],
            $this->product()
        );
        $this->assertSame(
            ['item_id' => 28, 'group_id' => 14, 'label' => 'Smiley', 'sku' => 'LOGO-SMILEY', 'image' => 'smiley.png'],
            $result['options'][0]['amerimark_image']
        );
    }

    public function testUploadsAndOtherOptionTypesAreLeftAlone(): void
    {
        $in = ['options' => [
            $this->entry('image_group', '{"title":"logo.png"}'),
            $this->entry('drop_down', '5'),
        ]];
        $out = $this->plugin(['item_id' => 5])->afterGetOrderOptions($this->createMock(AbstractType::class), $in, $this->product());
        $this->assertSame($in, $out);
    }

    public function testImageThatCannotBeFoundGetsNoSnapshotInsteadOfFailing(): void
    {
        $in = ['options' => [$this->entry('image_group', '28')]];
        $out = $this->plugin(null)->afterGetOrderOptions($this->createMock(AbstractType::class), $in, $this->product());
        $this->assertSame($in, $out);
    }

    public function testResultWithoutOptionsIsUntouched(): void
    {
        $this->assertSame(
            ['info_buyRequest' => []],
            $this->plugin(null)->afterGetOrderOptions($this->createMock(AbstractType::class), ['info_buyRequest' => []], $this->product())
        );
    }
}
