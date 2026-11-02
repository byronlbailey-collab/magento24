/**
 * Copyright Amerimark. All rights reserved.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'jquery/validate'
], function ($, modal, $t) {
    'use strict';

    var validatorAdded = false;

    /**
     * "Required" passes with a chosen image, a file picked for upload, or an earlier upload that is kept.
     */
    function addValidator() {
        if (validatorAdded) {
            return;
        }
        validatorAdded = true;
        $.validator.addMethod('amerimark-image-choice', function (value, element) {
            var $box = $(element).closest('[data-role="amerimark-image-option"]'),
                hasFile = $box.find('[data-role="file"]').val() !== undefined &&
                    $box.find('[data-role="file"]').val() !== '',
                keepsSaved = $box.find('[data-role="file-action"]').val() === 'save_old';

            if (!$box.hasClass('required')) {
                return true;
            }

            return !!value || hasFile || keepsSaved;
        }, function (params, element) {
            var $box = $(element).closest('[data-role="amerimark-image-option"]');

            return $box.data('uploadAllowed') ?
                $t('Please choose an image or upload your own logo.') :
                $t('Please choose an image.');
        });
    }

    /**
     * Image option widget: a button opens a popup with a grid of thumbnails, the chosen image is shown next to the
     * button, and (when enabled) a file input lets the shopper upload their own file instead.
     *
     * @param {Object} config
     * @param {HTMLElement} element
     */
    return function (config, element) {
        var $root = $(element),
            $value = $root.find('[data-role="value"]'),
            $selected = $root.find('[data-role="selected"]'),
            $selectedThumb = $root.find('[data-role="selected-thumb"]'),
            $selectedLabel = $root.find('[data-role="selected-label"]'),
            $open = $root.find('[data-role="open"]'),
            $clear = $root.find('[data-role="clear"]'),
            $file = $root.find('[data-role="file"]'),
            $fileAction = $root.find('[data-role="file-action"]'),
            $saved = $root.find('[data-role="saved-upload"]'),
            itemsById = {},
            $content = null;

        addValidator();
        $root.data('uploadAllowed', !!config.uploadAllowed);
        $.each(config.items || [], function (i, item) {
            itemsById[item.id] = item;
        });

        /**
         * Show/hide the chosen-image preview.
         */
        function render() {
            var item = itemsById[$value.val()];

            if (item) {
                $selectedThumb.css('background-image', 'url("' + item.thumb + '")');
                $selectedLabel.text(item.label);
                $selected.prop('hidden', false);
            } else {
                $selected.prop('hidden', true);
            }

            if ($content) {
                $content.find('.imageoption-item').each(function () {
                    var $btn = $(this),
                        isSelected = String($btn.data('id')) === String($value.val());

                    $btn.toggleClass('is-selected', isSelected).attr('aria-pressed', isSelected ? 'true' : 'false');
                });
            }
        }

        /**
         * Revalidate this option after a change so a stale error message goes away.
         */
        function revalidate() {
            if ($.fn.valid && $value.closest('form').data('validator')) {
                $value.valid();
            }
        }

        /**
         * @param {String|Number} id empty string clears the choice
         */
        function setImage(id) {
            $value.val(id);

            if (id) {
                // an image replaces any file picked or kept
                $file.val('');
                $fileAction.val('save_new');
                $saved.hide();
            }
            render();
            $value.trigger('change');
            revalidate();
        }

        /**
         * Build the popup the first time it is needed.
         */
        function buildModal() {
            var $grid = $('<ul class="imageoption-grid"></ul>');

            $content = $('<div class="imageoption-modal"></div>').append($grid);

            $.each(config.items || [], function (i, item) {
                var $button = $('<button type="button" class="imageoption-item"></button>')
                    .attr({
                        title: item.label,
                        'aria-label': item.label
                    })
                    .data('id', item.id)
                    .append($('<img/>').attr({
                        src: item.thumb,
                        alt: item.label,
                        loading: 'lazy'
                    }))
                    .append($('<span class="imageoption-item__label"></span>').text(item.label));

                $button.on('click', function () {
                    setImage(item.id);
                    $content.modal('closeModal');
                });
                $grid.append($('<li></li>').append($button));
            });

            modal({
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Choose an image'),
                modalClass: 'imageoption-modal-wrapper',
                buttons: []
            }, $content);
        }

        $open.on('click', function () {
            if (!$content) {
                buildModal();
            }
            render();
            $content.modal('openModal');
        });

        $clear.on('click', function () {
            setImage('');
            $open.trigger('focus');
        });

        $file.on('change', function () {
            if ($file.val()) {
                // a picked file replaces the chosen image
                $value.val('');
                $fileAction.val('save_new');
                $saved.hide();
                render();
            }
            revalidate();
        });

        render();
    };
});
