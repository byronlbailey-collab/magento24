<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Test\Unit\Model\Product\Option\Validator;

use Amerimark\ColorOption\Model\OptionColors;
use Amerimark\ColorOption\Model\Product\Option\Validator\ColorSwatch;
use Magento\Catalog\Model\Config\Source\Product\Options\Price;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\ProductOptions\ConfigInterface;
use Magento\Framework\Locale\FormatInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ColorSwatchTest extends TestCase
{
    private OptionColors&MockObject $colors;
    private ColorSwatch $validator;

    protected function setUp(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getAll')->willReturn([['types' => [['name' => 'color_swatch']]]]);
        $price = $this->createMock(Price::class);
        $price->method('toOptionArray')->willReturn([['value' => 'fixed'], ['value' => 'percent'], ['value' => 'fxline']]);
        $locale = $this->createMock(FormatInterface::class);
        $locale->method('getNumber')->willReturnArgument(0);
        $this->colors = $this->createMock(OptionColors::class);
        $this->validator = new ColorSwatch($config, $price, $this->colors, $locale);
    }

    private function option(array $data): Option
    {
        $option = $this->createPartialMock(Option::class, []);
        $option->setData($data + ['title' => 'Imprint Color', 'type' => 'color_swatch', 'color_group_id' => 1]);
        return $option;
    }

    private function row(int $colorId, array $extra = []): array
    {
        return $extra + ['color_id' => $colorId, 'title' => 'x', 'price' => '1', 'price_type' => 'fixed'];
    }

    private function catalog(array $colors): void
    {
        $this->colors->method('groupExists')->willReturn(true);
        $this->colors->method('getColors')->willReturn($colors);
    }

    public function testValidOption(): void
    {
        $this->catalog([
            1 => ['group_id' => 1, 'name' => 'Red'],
            2 => ['group_id' => 1, 'name' => 'Blue'],
        ]);

        $this->assertTrue($this->validator->isValid($this->option(['values' => [$this->row(1), $this->row(2)]])));
    }

    public function testGroupIsRequired(): void
    {
        $this->colors->method('groupExists')->willReturn(false);

        $this->assertFalse($this->validator->isValid($this->option(['values' => [$this->row(1)]])));
        $this->assertStringContainsString('color group', implode(' ', $this->validator->getMessages()));
    }

    public function testNeedsAtLeastOneColor(): void
    {
        $this->colors->method('groupExists')->willReturn(true);

        $this->assertFalse($this->validator->isValid($this->option(['values' => []])));
        $this->assertFalse($this->validator->isValid($this->option(['values' => [['option_type_id' => 3, 'is_delete' => 1]]])));
    }

    public function testSameNameTwiceIsRejectedEvenWithDifferentCase(): void
    {
        $this->catalog([
            1 => ['group_id' => 1, 'name' => 'Red'],
            2 => ['group_id' => 1, 'name' => ' red '],
        ]);

        $this->assertFalse($this->validator->isValid($this->option(['values' => [$this->row(1), $this->row(2)]])));
        $this->assertStringContainsString('more than once', implode(' ', $this->validator->getMessages()));
    }

    public function testDeletedRowsDoNotCountTowardsDuplicates(): void
    {
        $this->catalog([
            1 => ['group_id' => 1, 'name' => 'Red'],
            2 => ['group_id' => 1, 'name' => 'Red'],
        ]);

        $this->assertTrue($this->validator->isValid($this->option(['values' => [
            $this->row(1),
            $this->row(2, ['is_delete' => 1, 'option_type_id' => 9]),
        ]])));
    }

    public function testColorsMustComeFromTheOptionsGroup(): void
    {
        $this->catalog([1 => ['group_id' => 2, 'name' => 'Red']]);

        $this->assertFalse($this->validator->isValid($this->option(['values' => [$this->row(1)]])));
        $this->assertStringContainsString('not in the selected color group', implode(' ', $this->validator->getMessages()));
    }

    public function testUnknownColorIsRejected(): void
    {
        $this->catalog([]);

        $this->assertFalse($this->validator->isValid($this->option(['values' => [$this->row(99)]])));
    }

    public function testInvalidPriceIsRejectedLikeADropDown(): void
    {
        $this->catalog([1 => ['group_id' => 1, 'name' => 'Red']]);

        $this->assertFalse($this->validator->isValid(
            $this->option(['values' => [$this->row(1, ['price' => 'abc'])]])
        ));
    }
}
