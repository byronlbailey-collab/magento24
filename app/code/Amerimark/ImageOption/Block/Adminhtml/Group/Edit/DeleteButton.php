<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Block\Adminhtml\Group\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        if (!$this->getGroupId()) {
            return [];
        }
        $message = $this->context->getEscaper()->escapeJs(
            $this->context->getEscaper()->escapeHtml(__('Delete this group? This cannot be undone.'))
        );
        return [
            'label' => __('Delete Group'),
            'class' => 'delete',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {data: {}})",
                $message,
                $this->getUrl('*/*/delete', ['group_id' => $this->getGroupId()])
            ),
            'sort_order' => 20,
        ];
    }
}
