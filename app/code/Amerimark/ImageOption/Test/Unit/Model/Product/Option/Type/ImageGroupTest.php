<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Model\Product\Option\Type;

use Amerimark\ImageOption\Model\GroupItems;
use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup;
use Magento\Catalog\Helper\Product\Validator\ProductOptionValidator;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\File\ValidatorFile;
use Magento\Catalog\Model\Product\Option\Type\File\ValidatorInfo;
use Magento\Catalog\Model\Product\Option\UrlBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImageGroupTest extends TestCase
{
    private const ITEM = [
        'item_id' => 25, 'label' => 'Smiley', 'sku' => 'LOGO-SMILEY', 'image' => 'a.png',
        'url' => 'https://x/a.png', 'thumb' => 'https://x/t/a.png',
    ];

    private function type(array $optionData, ?array $activeItem, bool $required = true, array $allItems = []): ImageGroup
    {
        $groupItems = $this->createMock(GroupItems::class);
        $groupItems->method('findActiveItem')->willReturn($activeItem);
        $groupItems->method('findItem')->willReturn($activeItem);
        $groupItems->method('getItemsForOption')->willReturn($allItems);

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES));
        $escaper->method('escapeHtmlAttr')->willReturnCallback(fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES));
        $escaper->method('escapeUrl')->willReturnCallback(fn ($v) => (string)$v);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($this->createMock(\Magento\Framework\Filesystem\Directory\ReadInterface::class));
        $filesystem->method('getDirectoryWrite')->willReturn($this->createMock(\Magento\Framework\Filesystem\Directory\WriteInterface::class));

        $type = new ImageGroup(
            $this->createMock(Session::class),
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(OptionFactory::class),
            $this->createMock(Database::class),
            $this->createMock(ValidatorInfo::class),
            $this->createMock(ValidatorFile::class),
            $this->createMock(UrlBuilder::class),
            $escaper,
            $groupItems,
            [],
            $filesystem,
            new Json(),
            $this->createMock(\Magento\Catalog\Helper\Product::class),
            $this->createMock(ProductOptionValidator::class)
        );
        $option = $this->createPartialMock(Option::class, []);
        $option->setData($optionData + ['option_id' => 10, 'title' => 'Imprint Logo', 'is_require' => $required ? 1 : 0]);
        $option->setIdFieldName('option_id');
        $type->setOption($option);
        $type->setProduct($this->createMock(\Magento\Catalog\Model\Product::class));
        $type->setRequest(new DataObject());
        return $type;
    }

    #[DataProvider('imageValues')]
    public function testIsImageValue($value, bool $expected): void
    {
        $this->assertSame($expected, ImageGroup::isImageValue($value));
    }

    public static function imageValues(): array
    {
        return [
            'digit string' => ['25', true],
            'int' => [25, true],
            'serialized upload info' => ['{"type":"image\/png","title":"a.png"}', false],
            'decimal' => ['25.5', false],
            'empty' => ['', false],
            'array' => [[25], false],
            'null' => [null, false],
        ];
    }

    public function testValidImageChoiceIsAcceptedAndPreparedAsPlainId(): void
    {
        $type = $this->type(['image_allow_upload' => 0], self::ITEM);
        $type->validateUserValue([10 => '25']);
        $this->assertTrue($type->getIsValid());
        $this->assertSame('25', $type->prepareForCart());
        $this->assertSame(['10' => '25'], array_map('strval', $type->getRequest()->getOptions()));
    }

    public function testUnavailableImageIsRejected(): void
    {
        $type = $this->type(['image_allow_upload' => 0], null);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please choose one of the available images for "Imprint Logo".');
        $type->validateUserValue([10 => '9999']);
    }

    #[DataProvider('garbage')]
    public function testGarbageIsRejectedNotTreatedAsAnImage(string $raw): void
    {
        $type = $this->type(['image_allow_upload' => 0], null);
        $this->expectException(LocalizedException::class);
        $type->validateUserValue([10 => $raw]);
    }

    public static function garbage(): array
    {
        return [["abc'--"], ['25.5'], ['-1'], ['1 OR 1=1'], ['<script>']];
    }

    public function testRequiredOptionWithNothingChosenAndNoUploadFails(): void
    {
        $type = $this->type(['image_allow_upload' => 0], self::ITEM, true);
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage("required option(s) weren't entered");
        $type->validateUserValue([]);
    }

    public function testOptionalOptionWithNothingChosenIsSkipped(): void
    {
        $type = $this->type(['image_allow_upload' => 0], self::ITEM, false);
        $type->validateUserValue([]);
        $this->assertNull($type->prepareForCart());
    }

    public function testFormattedValueEscapesLabelAndUsesOnlySpanMarkup(): void
    {
        $item = ['label' => 'Star <b>x</b>'] + self::ITEM;
        $type = $this->type([], $item);
        $html = $type->getFormattedOptionValue('25');
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('Star &lt;b&gt;x&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('amerimark-image-option__thumb', $html);
        $this->assertSame('Star <b>x</b>', $type->getPrintableOptionValue('25'));
    }

    public function testDeletedImageShowsAnUnavailableNoticeInsteadOfFailing(): void
    {
        $type = $this->type([], null);
        $this->assertStringContainsString('no longer available', $type->getFormattedOptionValue('25'));
    }

    public function testImageSkuIsAppendedLikeADropDownValueSku(): void
    {
        $type = $this->type([], self::ITEM);
        $this->assertSame('LOGO-SMILEY', $type->getOptionSku('25', '-'));
        $this->assertSame('', $type->getOptionSku('{"title":"x"}', '-'));
    }

    public function testImageOptionHasNoPrice(): void
    {
        $this->assertSame(0.0, $this->type([], self::ITEM)->getOptionPrice('25', 100.0));
    }

    public function testCustomizedViewPrefersTheOrderTimeSnapshot(): void
    {
        $type = $this->type([], self::ITEM);
        $this->assertSame('<snapshot>', $type->getCustomizedView(['option_value' => '25', 'value' => '<snapshot>']));
    }

    public function testEditedLabelMapsBackToTheImageId(): void
    {
        $type = $this->type([], self::ITEM, true, [self::ITEM]);
        $this->assertSame('25', $type->parseOptionValue(' smiley ', []));
    }
}
