<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\FixedLine\Model;

/**
 * Data keys used on quote items and tax detail items.
 */
final class FeeKeys
{
    /** Transient (not persisted): configured fee, recomputed on every calcRowTotal. */
    public const CONFIGURED = 'fxline_fee_configured';
    public const BASE_CONFIGURED = 'base_fxline_fee_configured';

    /** Persisted columns: fee excluding tax, as contained in row_total. */
    public const AMOUNT = 'fxline_amount';
    public const BASE_AMOUNT = 'base_fxline_amount';

    /** Persisted columns: fee including tax, as contained in row_total_incl_tax. */
    public const AMOUNT_INCL_TAX = 'fxline_amount_incl_tax';
    public const BASE_AMOUNT_INCL_TAX = 'base_fxline_amount_incl_tax';

    /** Stash on TaxDetailsItem (AbstractExtensibleModel) carrying fee-only amounts. */
    public const DETAILS_ROW_TOTAL = 'fxline_row_total';
    public const DETAILS_ROW_TOTAL_INCL_TAX = 'fxline_row_total_incl_tax';

    /** Code type of the temporary qty-1 companion tax row. */
    public const TAX_ITEM_TYPE = 'fxline';
    public const TAX_ITEM_CODE_PREFIX = 'fxline-';
}
