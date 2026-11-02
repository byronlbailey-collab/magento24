<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Plugin\Checkout;

use Amerimark\ImageOption\Model\QuoteImageValidator;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

/**
 * The checkout page only looks at the quote's error flag and does not recollect totals first, so flag
 * unavailable images whenever the checkout session hands out the quote.
 */
class SessionQuoteImageCheckPlugin
{
    /**
     * @param QuoteImageValidator $validator
     */
    public function __construct(private readonly QuoteImageValidator $validator)
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
