<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Ui;

use PHPUnit\Framework\TestCase;

/**
 * Guards the edit form's UI component definition against mistakes that only show up in a browser.
 */
class FormDefinitionTest extends TestCase
{
    private \SimpleXMLElement $form;

    protected function setUp(): void
    {
        $file = dirname(__DIR__, 3) . '/view/adminhtml/ui_component/amerimark_imageoption_group_form.xml';
        $this->form = simplexml_load_file($file);
    }

    public function testDataSourceDeclaresItsJavaScriptProvider(): void
    {
        // Without it nothing creates the data source, the form waits for it forever and the page shows a spinner.
        $provider = $this->form->xpath(
            '//dataSource/argument[@name="data"]/item[@name="js_config"]/item[@name="component"]'
        );
        $this->assertNotEmpty($provider);
        $this->assertSame('Magento_Ui/js/form/provider', trim((string)$provider[0]));
    }

    public function testImageFieldHasNoMediaGalleryPicker(): void
    {
        // Gallery picks live outside this group's directory and would be refused on save.
        $tmpl = $this->form->xpath('//field[@name="image"]/settings/elementTmpl');
        $this->assertSame('ui/form/element/uploader/uploader', trim((string)$tmpl[0]));
    }

    public function testUploadGoesThroughTheModuleUploadController(): void
    {
        $url = $this->form->xpath('//param[@name="url"]');
        $this->assertSame('amerimark_imageoption/group/upload', (string)$url[0]['path']);
    }
}
