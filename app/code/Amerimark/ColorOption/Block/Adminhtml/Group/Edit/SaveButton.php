<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Block\Adminhtml\Group\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save Group'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['buttonAdapter' => ['actions' => [[
                    'targetName' => 'amerimark_coloroption_group_form.amerimark_coloroption_group_form',
                    'actionName' => 'save',
                    'params' => [false],
                ]]]],
            ],
            'sort_order' => 40,
        ];
    }
}
