<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Plugin\Catalog;

use Amerimark\ImageOption\Plugin\Catalog\OptionNormalizePlugin;
use Magento\Catalog\Model\Product\Option;
use PHPUnit\Framework\TestCase;

class OptionNormalizePluginTest extends TestCase
{
    private function option(array $data): Option
    {
        $option = $this->createPartialMock(Option::class, []);
        $option->setData($data);
        return $option;
    }

    public function testOtherTypesDropEmptyStringAndStaleSettings(): void
    {
        $option = $this->option(['type' => 'drop_down', 'image_group_id' => '', 'image_allow_upload' => '1', 'image_upload_ext' => 'png']);
        (new OptionNormalizePlugin())->beforeBeforeSave($option);
        $this->assertNull($option->getData('image_group_id'));
        $this->assertSame(0, $option->getData('image_allow_upload'));
        $this->assertNull($option->getData('image_upload_ext'));
    }

    public function testImageGroupKeepsGroupAndNormalizesSettings(): void
    {
        $option = $this->option([
            'type' => 'image_group', 'image_group_id' => '12', 'image_allow_upload' => '1',
            'image_upload_ext' => ' .PNG,  jpg ;png, GIF ',
        ]);
        (new OptionNormalizePlugin())->beforeBeforeSave($option);
        $this->assertSame(12, $option->getData('image_group_id'));
        $this->assertSame(1, $option->getData('image_allow_upload'));
        $this->assertSame('png, jpg, gif', $option->getData('image_upload_ext'));
    }

    public function testMissingGroupBecomesNullNotZero(): void
    {
        $option = $this->option(['type' => 'image_group', 'image_group_id' => '0', 'image_allow_upload' => '0']);
        (new OptionNormalizePlugin())->beforeBeforeSave($option);
        $this->assertNull($option->getData('image_group_id'));
        $this->assertSame(0, $option->getData('image_allow_upload'));
        $this->assertNull($option->getData('image_upload_ext'));
    }
}
