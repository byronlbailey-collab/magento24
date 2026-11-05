<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Plugin\Quote;

use Amerimark\ImageOption\Plugin\Quote\CopyUploadsToOrderPlugin;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use PHPUnit\Framework\TestCase;

class CopyUploadsToOrderPluginTest extends TestCase
{
    private function spy(?\Throwable $failWith = null): object
    {
        return new class($failWith) {
            public int $copies = 0;
            public $itemOption = null;

            public function __construct(private readonly ?\Throwable $failWith)
            {
            }

            public function setQuoteItemOption($itemOption): self
            {
                $this->itemOption = $itemOption;
                return $this;
            }

            public function copyQuoteToOrder(): self
            {
                $this->copies++;
                if ($this->failWith) {
                    throw $this->failWith;
                }
                return $this;
            }
        };
    }

    private function item(string $optionType, object $group): Item
    {
        $option = $this->createMock(Option::class);
        $option->method('getType')->willReturn($optionType);
        $option->method('groupFactory')->willReturn($group);
        $product = $this->createMock(Product::class);
        $product->method('getOptionById')->willReturn($option);
        $item = $this->createMock(Item::class);
        $item->method('getOptions')->willReturn([new DataObject(['code' => 'option_11'])]);
        $item->method('getProduct')->willReturn($product);
        return $item;
    }

    public function testImageGroupOptionTriggersTheFileCopy(): void
    {
        $group = $this->spy();
        (new CopyUploadsToOrderPlugin())->beforeConvert($this->createMock(ToOrderItem::class), $this->item('image_group', $group));
        $this->assertSame(1, $group->copies);
        $this->assertSame('option_11', $group->itemOption->getData('code'));
    }

    public function testOtherOptionTypesAreNotTouchedHere(): void
    {
        $group = $this->spy();
        (new CopyUploadsToOrderPlugin())->beforeConvert($this->createMock(ToOrderItem::class), $this->item('file', $group));
        $this->assertSame(0, $group->copies);
    }

    public function testACopyFailureNeverBreaksOrderPlacement(): void
    {
        $group = $this->spy(new \RuntimeException('disk full'));
        (new CopyUploadsToOrderPlugin())->beforeConvert($this->createMock(ToOrderItem::class), $this->item('image_group', $group));
        $this->assertSame(1, $group->copies);
    }
}
