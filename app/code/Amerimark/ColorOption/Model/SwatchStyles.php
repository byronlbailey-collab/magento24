<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ColorOption\Model;

/**
 * The ways a color's circle can be drawn. A style is a code, a label and an optional CSS background image painted over
 * the color's hex value; the hex always stays as the fallback for places that cannot show the image (some email
 * clients) and is the whole look of the "solid" style.
 *
 * New styles are added in di.xml (argument "styles" of this class) - nothing else needs to change:
 *
 *   <item name="metallic" xsi:type="array">
 *       <item name="label" xsi:type="string">Metallic</item>
 *       <item name="image" xsi:type="string">linear-gradient(135deg, #f5f5f5, #9a9a9a 50%, #f5f5f5)</item>
 *   </item>
 */
class SwatchStyles
{
    public const SOLID = 'solid';

    /**
     * @param array<string, array{label: string, image?: string}> $styles code => definition
     */
    public function __construct(private readonly array $styles = [])
    {
    }

    /**
     * Known style codes with their labels, in registry order, for drop-downs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->all() as $code => $style) {
            $options[] = ['value' => $code, 'label' => (string)__($style['label'])];
        }
        return $options;
    }

    /**
     * Turns anything into a known style code (unknown or empty becomes solid).
     *
     * @param mixed $code
     * @return string
     */
    public function normalize($code): string
    {
        $code = is_string($code) ? trim($code) : '';
        return isset($this->all()[$code]) ? $code : self::SOLID;
    }

    /**
     * The CSS background-image of a style ('' when it is just the color).
     *
     * @param mixed $code
     * @return string
     */
    public function getImage($code): string
    {
        return (string)($this->all()[$this->normalize($code)]['image'] ?? '');
    }

    /**
     * Inline CSS that paints the circle: the color first (fallback), then the style's image over it.
     *
     * @param mixed $code
     * @param string $hex #RRGGBB
     * @return string
     */
    public function getInlineCss($code, string $hex): string
    {
        $css = 'background-color:' . $hex;
        $image = $this->getImage($code);
        return $image !== '' ? $css . ';background-image:' . $image : $css;
    }

    /**
     * Style code => CSS image, for the admin scripts that preview swatches.
     *
     * @return array<string, string>
     */
    public function getImageMap(): array
    {
        $map = [];
        foreach (array_keys($this->all()) as $code) {
            $map[$code] = $this->getImage($code);
        }
        return $map;
    }

    /**
     * Registry with the built-in solid style guaranteed to be first.
     *
     * @return array<string, array{label: string, image?: string}>
     */
    private function all(): array
    {
        return [self::SOLID => ['label' => 'Solid color']] + $this->styles;
    }
}
