<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Plugin\Checkout;

use Amerimark\ColorOption\Model\QuoteColorValidator;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

/**
 * The checkout page only looks at the quote's error flag and does not recollect totals first, so flag unavailable
 * colors whenever the checkout session hands out the quote.
 */
class SessionQuoteColorCheckPlugin
{
    /**
     * @param QuoteColorValidator $validator
     */
    public function __construct(private readonly QuoteColorValidator $validator)
    {
    }

    /**
     * @param Session $subject
     * @param Quote $result
     * @return Quote
     */
    public function afterGetQuote(Session $subject, $result)
    {
        if ($result && $result->getId()) {
            $this->validator->flagProblems($result);
        }
        return $result;
    }
}
