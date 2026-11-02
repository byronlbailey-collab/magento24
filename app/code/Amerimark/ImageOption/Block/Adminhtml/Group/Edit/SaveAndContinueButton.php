<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Block\Adminhtml\Group\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinueButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save and Continue Editing'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => ['buttonAdapter' => ['actions' => [[
                    'targetName' => 'amerimark_imageoption_group_form.amerimark_imageoption_group_form',
                    'actionName' => 'save',
                    'params' => [true, ['back' => 'continue']],
                ]]]],
            ],
            'sort_order' => 30,
        ];
    }
}
