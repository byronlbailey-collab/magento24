<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds public media URLs for group images.
 */
class ImageUrl
{
    public const MEDIA_PATH = 'amerimark/imageoption';

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(private readonly StoreManagerInterface $storeManager)
    {
    }

    /**
     * Media-relative path of a stored image file name.
     *
     * @param string $file
     * @return string
     */
    public function getRelativePath(string $file): string
    {
        return self::MEDIA_PATH . '/' . ltrim($file, '/');
    }

    /**
     * Public URL of any media-relative path.
     *
     * @param string $relativePath
     * @return string
     */
    public function getMediaUrl(string $relativePath): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($relativePath, '/');
    }

    /**
     * Public URL of a stored image file name.
     *
     * @param string $file
     * @return string
     */
    public function getUrl(string $file): string
    {
        return $this->getMediaUrl($this->getRelativePath($file));
    }
}
