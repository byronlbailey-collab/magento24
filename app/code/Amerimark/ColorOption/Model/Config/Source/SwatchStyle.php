<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model\Config\Source;

use Amerimark\ColorOption\Model\SwatchStyles;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Swatch style choices for the color group editor.
 */
class SwatchStyle implements OptionSourceInterface
{
    /**
     * @param SwatchStyles $styles
     */
    public function __construct(private readonly SwatchStyles $styles)
    {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return $this->styles->toOptionArray();
    }
}
