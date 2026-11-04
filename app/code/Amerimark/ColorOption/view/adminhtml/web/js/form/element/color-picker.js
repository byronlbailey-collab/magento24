/**
 * Copyright Amerimark. All rights reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    'jquery/colorpicker/js/colorpicker'
], function ($, Abstract) {
    'use strict';

    var DEFAULT_COLOR = '#C8102E';

    /**
     * @param {String} hex
     * @return {Number[]|null} [r, g, b] or null when the value is not a #RRGGBB color
     */
    function parseHex(hex) {
        var match = /^#?([0-9a-f]{6})$/i.exec(String(hex || '').trim()),
            n;

        if (!match) {
            return null;
        }
        n = parseInt(match[1], 16);

        return [n >> 16 & 255, n >> 8 & 255, n & 255]; // eslint-disable-line no-bitwise
    }

    /**
     * @param {*} channel
     * @return {String}
     */
    function channelToHex(channel) {
        var n = Math.max(0, Math.min(255, parseInt(channel, 10) || 0));

        return ('0' + n.toString(16)).slice(-2);
    }

    /**
     * Color entry for a color group row: a swatch that opens Magento's own color picker (saturation square, hue
     * slider, hex/RGB/HSB boxes), a hex box and R/G/B boxes, all kept in sync.
     */
    return Abstract.extend({
        defaults: {
            elementTmpl: 'Amerimark_ColorOption/form/element/color-picker',
            listens: {
                value: 'syncChannels'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            this._super().observe(['r', 'g', 'b']);

            return this;
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.syncChannels(this.value());

            return this;
        },

        /**
         * Show the typed/picked hex color in the R, G and B boxes.
         *
         * @param {String} hex
         */
        syncChannels: function (hex) {
            var rgb = parseHex(hex);

            this.r(rgb ? rgb[0] : '');
            this.g(rgb ? rgb[1] : '');
            this.b(rgb ? rgb[2] : '');
        },

        /**
         * A channel box was edited: rebuild the hex value.
         */
        onChannelChange: function () {
            this.value('#' + (channelToHex(this.r()) + channelToHex(this.g()) + channelToHex(this.b())).toUpperCase());
        },

        /**
         * Normalize a typed hex value (#abc, abc, c0ffee...) when the box loses focus.
         */
        onHexBlur: function () {
            var typed = String(this.value() || '').trim(),
                short = /^#?([0-9a-f])([0-9a-f])([0-9a-f])$/i.exec(typed),
                rgb;

            if (short) {
                typed = '#' + short[1] + short[1] + short[2] + short[2] + short[3] + short[3];
            }
            rgb = parseHex(typed);

            if (rgb) {
                this.value('#' + typed.replace('#', '').toUpperCase());
            }
        },

        /**
         * Magento's bundled picker only changes the color while the mouse *moves* after pressing down on the shade
         * square or the hue bar, so a plain click does nothing (it works only if the hand happens to move). Replay the
         * press position as a mouse move so a click picks the color under the pointer, and a drag still works.
         *
         * @param {jQuery} $swatch
         */
        enableClickToPick: function ($swatch) {
            var $picker = $('#' + $swatch.data('colorpickerId'));

            $picker.on('mousedown', '.colorpicker_color, .colorpicker_hue', function (event) {
                var move = $.Event('mousemove');

                move.pageX = event.pageX;
                move.pageY = event.pageY;
                $(document).trigger(move);
            });
        },

        /**
         * Attach Magento's color picker to the swatch button.
         *
         * @param {HTMLElement} element
         */
        initPicker: function (element) {
            var self = this,
                $swatch = $(element);

            $swatch.ColorPicker({
                color: parseHex(self.value()) ? self.value() : DEFAULT_COLOR,

                /**
                 * Open the picker on the current value.
                 */
                onShow: function () {
                    $swatch.ColorPickerSetColor(parseHex(self.value()) ? self.value() : DEFAULT_COLOR);
                },

                /**
                 * Live update while the admin drags.
                 *
                 * @param {Object} hsb
                 * @param {String} hex
                 */
                onChange: function (hsb, hex) {
                    self.value('#' + hex.toUpperCase());
                },

                /**
                 * OK button.
                 *
                 * @param {Object} hsb
                 * @param {String} hex
                 * @param {Object} rgb
                 * @param {HTMLElement} el
                 */
                onSubmit: function (hsb, hex, rgb, el) {
                    self.value('#' + hex.toUpperCase());
                    $(el).ColorPickerHide();
                }
            });

            this.enableClickToPick($swatch);
        }
    });
});
