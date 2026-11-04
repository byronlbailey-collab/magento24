<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Finds cart lines whose chosen color is no longer available (disabled or deleted since it was added).
 */
class QuoteColorValidator
{
    public const ERROR_ORIGIN = 'amerimark_coloroption';
    public const ERROR_CODE = 'color_unavailable';

    /**
     * @param OptionColors $colors
     */
    public function __construct(private readonly OptionColors $colors)
    {
    }

    /**
     * Problems per quote item.
     *
     * @param Quote $quote
     * @return array<int|string, array{item: AbstractItem, message: \Magento\Framework\Phrase}>
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
            if (!$option || $option->getType() !== OptionColors::OPTION_TYPE) {
                continue;
            }
            $selection = $item->getOptionByCode('option_' . $optionId);
            $value = $selection ? (int)$selection->getValue() : 0;
            if ($value <= 0) {
                continue;
            }
            $color = $this->colors->getSwatchData([$value])[$value] ?? null;
            if ($color === null || !$color['is_active']) {
                return __(
                    'The color you chose for "%1" on "%2" is no longer available. Please choose another color.',
                    $option->getTitle(),
                    $item->getName()
                );
            }
        }
        return null;
    }
}
