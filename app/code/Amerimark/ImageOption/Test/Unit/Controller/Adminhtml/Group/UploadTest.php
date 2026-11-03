<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Controller\Adminhtml\Group;

use Amerimark\ImageOption\Controller\Adminhtml\Group\Upload;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use PHPUnit\Framework\TestCase;

/**
 * Inside the image rows the browser posts the file under the field's own name and reports it in param_name;
 * a controller that hard-codes the name makes every upload fail ("array offset on null" in the framework uploader).
 */
class UploadTest extends TestCase
{
    private function controller(?string $paramName, ImageUploader $uploader, Json $json): Upload
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn ($key, $default = null) => $key === 'param_name' && $paramName !== null ? $paramName : $default);
        $factory = $this->createMock(ResultFactory::class);
        $factory->method('create')->willReturn($json);
        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultFactory')->willReturn($factory);
        return new Upload($context, $uploader);
    }

    public function testUsesTheFieldNameTheBrowserReports(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->once())->method('saveFileToTmpDir')->with('items[0][image]')->willReturn(['name' => 'a.png']);
        $json = $this->createMock(Json::class);
        $json->expects($this->once())->method('setData')->with(['name' => 'a.png'])->willReturnSelf();
        $this->controller('items[0][image]', $uploader, $json)->execute();
    }

    public function testFallsBackToTheSimpleFieldName(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->once())->method('saveFileToTmpDir')->with('image')->willReturn([]);
        $json = $this->createMock(Json::class);
        $json->method('setData')->willReturnSelf();
        $this->controller(null, $uploader, $json)->execute();
    }

    public function testUploadFailuresAreReturnedAsAnErrorNotAnException(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->method('saveFileToTmpDir')->willThrowException(new \RuntimeException('File validation failed.'));
        $json = $this->createMock(Json::class);
        $json->expects($this->once())->method('setData')->with($this->callback(fn ($d) => $d['error'] === 'File validation failed.'))->willReturnSelf();
        $this->controller('image', $uploader, $json)->execute();
    }
}
