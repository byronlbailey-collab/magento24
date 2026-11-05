<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Psr\Log\LoggerInterface;

/**
 * Creates (and caches) small thumbnails of group images for the storefront popup.
 */
class ImageResizer
{
    public const DEFAULT_SIZE = 120;

    /**
     * @param Filesystem $filesystem
     * @param AdapterFactory $adapterFactory
     * @param ImageUrl $imageUrl
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly AdapterFactory $adapterFactory,
        private readonly ImageUrl $imageUrl,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * URL of a square-bounded thumbnail; falls back to the original image when it cannot be generated.
     *
     * @param string $file stored image file name
     * @param int $size maximum width/height in pixels
     * @return string
     */
    public function getThumbnailUrl(string $file, int $size = self::DEFAULT_SIZE): string
    {
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $source = $this->imageUrl->getRelativePath($file);
        if (!$media->isFile($source)) {
            return $this->imageUrl->getUrl($file);
        }
        $target = ImageUrl::MEDIA_PATH . '/cache/' . $size . 'x' . $size . '/' . ltrim($file, '/');
        try {
            if (!$media->isFile($target)) {
                $image = $this->adapterFactory->create();
                $image->open($media->getAbsolutePath($source));
                $image->constrainOnly(true);
                $image->keepAspectRatio(true);
                $image->keepFrame(false);
                $image->keepTransparency(true);
                $image->resize($size, $size);
                $image->save($media->getAbsolutePath($target));
            }
            return $this->imageUrl->getMediaUrl($target);
        } catch (\Throwable $e) {
            $this->logger->warning('Amerimark_ImageOption: thumbnail failed for ' . $file . ': ' . $e->getMessage());
            return $this->imageUrl->getUrl($file);
        }
    }
}
