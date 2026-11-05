/**
 * Copyright Amerimark. All rights reserved.
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     * Color option: round swatches (radio buttons). The name of the color, and its price when it has one, is shown
     * while hovering over / focusing a swatch and stays shown for the chosen one.
     *
     * @param {Object} config
     * @param {HTMLElement} element
     */
    return function (config, element) {
        var $root = $(element),
            $status = $root.find('[data-role="status-text"]'),
            $clear = $root.find('[data-role="clear"]'),
            $inputs = $root.find('.colorswatch-input');

        /**
         * @param {jQuery} $swatch
         * @return {String}
         */
        function describe($swatch) {
            return $.trim($swatch.attr('data-name') + ' ' + ($swatch.attr('data-price') || ''));
        }

        /**
         * Show the chosen color (or nothing).
         */
        function showSelected() {
            var $checked = $inputs.filter(':checked').closest('.colorswatch');

            $status.text($checked.length ? describe($checked) : '');
            $clear.prop('hidden', !$checked.length);
        }

        /**
         * The hidden radios are not re-checked by the form validator on click: once "required" has been reported, check
         * again so the message goes away as soon as a color is chosen.
         */
        function revalidate() {
            var $first = $inputs.first();

            if ($inputs.hasClass('mage-error') && $first.closest('form').data('validator')) {
                $first.valid();
            }
        }

        $root.on('mouseenter focusin', '.colorswatch', function () {
            $status.text(describe($(this)));
        });
        $root.on('mouseleave focusout', '.colorswatch', showSelected);
        $inputs.on('change', function () {
            showSelected();
            revalidate();
        });

        $clear.on('click', function () {
            $inputs.prop('checked', false).first().trigger('change');
            showSelected();
        });

        showSelected();
    };
});
