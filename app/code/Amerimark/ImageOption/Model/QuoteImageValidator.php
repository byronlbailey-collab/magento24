<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Model;

use Amerimark\ImageOption\Model\Product\Option\Type\ImageGroup;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Finds cart lines whose chosen image is no longer available (image or group disabled or deleted since it was added).
 */
class QuoteImageValidator
{
    public const ERROR_ORIGIN = 'amerimark_imageoption';
    public const ERROR_CODE = 'image_unavailable';

    /**
     * @param GroupItems $groupItems
     */
    public function __construct(private readonly GroupItems $groupItems)
    {
    }

    /**
     * Problems per quote item id.
     *
     * @param Quote $quote
     * @return array<int|string, \Magento\Framework\Phrase>
     */
    public function getProblems(Quote $quote): array
    {
        $problems = [];
        foreach ($quote->getAllItems() as $item) {
            $message = $this->getItemProblem($item);
            if ($message !== null) {
                $problems[$item->getId() ?: spl_object_id($item)] = ['item' => $item, 'message' => $message];
            }
        }
        return $problems;
    }

    /**
     * Flag affected lines and the quote with an error (replacing earlier flags from this module, so calling it
     * repeatedly does not duplicate messages). Checkout redirects back to the cart while the quote has an error.
     *
     * @param Quote $quote
     * @return void
     */
    public function flagProblems(Quote $quote): void
    {
        $params = ['origin' => self::ERROR_ORIGIN, 'code' => self::ERROR_CODE];
        $quote->removeErrorInfosByParams('error', $params);
        foreach ($quote->getAllItems() as $item) {
            $item->removeErrorInfosByParams($params);
        }
        foreach ($this->getProblems($quote) as $problem) {
            $problem['item']->addErrorInfo(self::ERROR_ORIGIN, self::ERROR_CODE, $problem['message']);
            $quote->addErrorInfo('error', self::ERROR_ORIGIN, self::ERROR_CODE, $problem['message']);
        }
    }

    /**
     * @param AbstractItem $item
     * @return \Magento\Framework\Phrase|null
     */
    private function getItemProblem(AbstractItem $item)
    {
        $ids = $item->getOptionByCode('option_ids');
        $product = $item->getProduct();
        if (!$ids || !$product || (string)$ids->getValue() === '') {
            return null;
        }
        foreach (explode(',', (string)$ids->getValue()) as $optionId) {
            $option = $product->getOptionById($optionId);
            if (!$option || $option->getType() !== ImageGroup::OPTION_TYPE) {
                continue;
            }
            $selection = $item->getOptionByCode('option_' . $optionId);
            $value = $selection ? $selection->getValue() : null;
            if (ImageGroup::isImageValue($value) && !$this->groupItems->findActiveItem($option, (int)$value)) {
                return __(
                    'The image you chose for "%1" on "%2" is no longer available. Please choose another image.',
                    $option->getTitle(),
                    $item->getName()
                );
            }
        }
        return null;
    }
}
