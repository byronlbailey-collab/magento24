<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Plugin\Quote;

use Amerimark\ImageOption\Model\QuoteImageValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteValidator;

/**
 * Blocks checkout while a cart line carries an image that has been disabled or deleted:
 * flags the line and the quote with an error (cart page shows it, checkout redirects back to the cart), and refuses
 * to submit the order as a last line of defence.
 */
class QuoteImageAvailabilityPlugin
{
    /**
     * @param QuoteImageValidator $validator
     */
    public function __construct(private readonly QuoteImageValidator $validator)
    {
    }

    /**
     * @param Quote $subject
     * @param Quote $result
     * @return Quote
     */
    public function afterCollectTotals(Quote $subject, $result)
    {
        $this->validator->flagProblems($subject);
        return $result;
    }

    /**
     * @param QuoteValidator $subject
     * @param Quote $quote
     * @return void
     * @throws LocalizedException
     */
    public function beforeValidateBeforeSubmit(QuoteValidator $subject, Quote $quote): void
    {
        foreach ($this->validator->getProblems($quote) as $problem) {
            throw new LocalizedException($problem['message']);
        }
    }
}
