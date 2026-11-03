<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Block\Adminhtml\Group\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    /**
     * @param Context $context
     */
    public function __construct(protected readonly Context $context)
    {
    }

    /**
     * @return int|null
     */
    public function getGroupId(): ?int
    {
        $id = (int)$this->context->getRequest()->getParam('group_id');
        return $id ?: null;
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
