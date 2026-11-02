<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Model;

use Amerimark\ImageOption\Model\GroupItems;
use Amerimark\ImageOption\Model\QuoteImageValidator;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class QuoteImageValidatorTest extends TestCase
{
    private function item(string $optionType, ?string $value, int $id = 1): Item
    {
        $option = $this->createPartialMock(Option::class, []);
        $option->setData(['option_id' => 10, 'type' => $optionType, 'title' => 'Imprint Logo']);
        $product = $this->createMock(Product::class);
        $product->method('getOptionById')->willReturn($option);
        $item = $this->createMock(Item::class);
        $item->method('getId')->willReturn($id);
        $item->method('getName')->willReturn('Gummies');
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->willReturnMap([
            ['option_ids', new DataObject(['value' => '10'])],
            ['option_10', $value === null ? null : new DataObject(['value' => $value])],
        ]);
        return $item;
    }

    private function quote(array $items): Quote
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllItems')->willReturn($items);
        return $quote;
    }

    public function testAvailableImageHasNoProblem(): void
    {
        $groupItems = $this->createMock(GroupItems::class);
        $groupItems->method('findActiveItem')->willReturn(['item_id' => 25]);
        $this->assertSame([], (new QuoteImageValidator($groupItems))->getProblems($this->quote([$this->item('image_group', '25')])));
    }

    public function testUnavailableImageIsReportedWithAReadableMessage(): void
    {
        $groupItems = $this->createMock(GroupItems::class);
        $groupItems->method('findActiveItem')->willReturn(null);
        $problems = (new QuoteImageValidator($groupItems))->getProblems($this->quote([$this->item('image_group', '25')]));
        $this->assertCount(1, $problems);
        $this->assertStringContainsString('no longer available', (string)$problems[1]['message']);
        $this->assertStringContainsString('Imprint Logo', (string)$problems[1]['message']);
    }

    public function testUploadsAndOtherOptionTypesAreNotChecked(): void
    {
        $groupItems = $this->createMock(GroupItems::class);
        $groupItems->expects($this->never())->method('findActiveItem');
        $validator = new QuoteImageValidator($groupItems);
        $this->assertSame([], $validator->getProblems($this->quote([
            $this->item('image_group', '{"title":"logo.png"}', 1),
            $this->item('drop_down', '25', 2),
        ])));
    }

    public function testFlaggingMarksTheLineAndTheQuoteAndClearsEarlierFlags(): void
    {
        $groupItems = $this->createMock(GroupItems::class);
        $groupItems->method('findActiveItem')->willReturn(null);
        $item = $this->item('image_group', '25');
        $item->expects($this->once())->method('removeErrorInfosByParams');
        $item->expects($this->once())->method('addErrorInfo')
            ->with(QuoteImageValidator::ERROR_ORIGIN, QuoteImageValidator::ERROR_CODE, $this->anything());
        $quote = $this->quote([$item]);
        $quote->expects($this->once())->method('removeErrorInfosByParams')->with('error', $this->anything());
        $quote->expects($this->once())->method('addErrorInfo')
            ->with('error', QuoteImageValidator::ERROR_ORIGIN, QuoteImageValidator::ERROR_CODE, $this->anything());
        (new QuoteImageValidator($groupItems))->flagProblems($quote);
    }
}
