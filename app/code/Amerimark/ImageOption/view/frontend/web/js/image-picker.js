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

    var validatorAdded = false,
        HIDDEN_INPUT_CSS = {
            position: 'absolute',
            left: 0,
            width: '1px',
            height: '1px',
            padding: 0,
            border: 0,
            opacity: 0,
            overflow: 'hidden',
            pointerEvents: 'none'
        },
        THUMB_SIZE = 56;

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
     * Thumbnail element. A real <img>, sized by attributes, so it shows even if the theme does not load the module
     * stylesheet.
     *
     * @param {String} src
     * @param {String} alt
     * @return {jQuery}
     */
    function imageNode(src, alt) {
        return $('<img/>')
            .attr({
                src: src,
                alt: alt,
                width: THUMB_SIZE,
                height: THUMB_SIZE
            })
            .css({
                display: 'block',
                width: THUMB_SIZE + 'px',
                height: THUMB_SIZE + 'px',
                objectFit: 'contain'
            });
    }

    /**
     * Stand-in thumbnail for a file that is not an image (e.g. a PDF): the extension in a box.
     *
     * @param {String} fileName
     * @return {jQuery}
     */
    function badgeNode(fileName) {
        var ext = (/\.([A-Za-z0-9]{1,5})$/.exec(fileName) || [])[1] || 'FILE';

        return $('<span class="imageoption-file-badge"></span>')
            .text(ext.toUpperCase())
            .css({
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: THUMB_SIZE + 'px',
                height: THUMB_SIZE + 'px',
                fontWeight: 600,
                fontSize: '13px'
            });
    }

    /**
     * Image option widget: a button opens a popup with a grid of thumbnails and, when enabled, an upload button for
     * the shopper's own file. The choice (image or upload) is shown next to the button with its name.
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
            $selectedKind = $root.find('[data-role="selected-kind"]'),
            $open = $root.find('[data-role="open"]'),
            $clear = $root.find('[data-role="clear"]'),
            $file = $root.find('[data-role="file"]'),
            $fileAction = $root.find('[data-role="file-action"]'),
            itemsById = {},
            objectUrl = null,
            $content = null;

        addValidator();
        $root.data('uploadAllowed', !!config.uploadAllowed);
        $.each(config.items || [], function (i, item) {
            itemsById[item.id] = item;
        });

        // Must work without the stylesheet: keep the validated value and the real file input out of sight.
        $value.css(HIDDEN_INPUT_CSS);
        $file.css(HIDDEN_INPUT_CSS);

        /**
         * @return {File|null} the file picked for upload, if any
         */
        function pickedFile() {
            return $file.length && $file[0].files && $file[0].files[0] ? $file[0].files[0] : null;
        }

        /**
         * Show the shopper's current choice (image, new upload or kept upload), or hide the preview.
         */
        function render() {
            var item = itemsById[$value.val()],
                file = pickedFile();

            if (objectUrl) {
                window.URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            if (item) {
                $selectedThumb.empty().append(imageNode(item.thumb, item.label));
                $selectedLabel.text(item.label);
                $selectedKind.text($t('Selected image'));
                $selected.prop('hidden', false);
            } else if (file) {
                if (/^image\//.test(file.type) && window.URL && window.URL.createObjectURL) {
                    objectUrl = window.URL.createObjectURL(file);
                    $selectedThumb.empty().append(imageNode(objectUrl, file.name));
                } else {
                    $selectedThumb.empty().append(badgeNode(file.name));
                }
                $selectedLabel.text(file.name);
                $selectedKind.text($t('Your upload'));
                $selected.prop('hidden', false);
            } else if ($fileAction.val() === 'save_old' && config.hasSavedUpload) {
                $selectedThumb.empty().append(badgeNode(config.savedUploadTitle || ''));
                $selectedLabel.text(config.savedUploadTitle || '');
                $selectedKind.text($t('Your saved upload'));
                $selected.prop('hidden', false);
            } else {
                $selectedThumb.empty();
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
                if ($file.length) {
                    $file.val('');
                }
                $fileAction.val('save_new');
            }
            render();
            $value.trigger('change');
            revalidate();
        }

        /**
         * Build the popup the first time it is needed.
         */
        function buildModal() {
            var $grid = $('<ul class="imageoption-grid"></ul>'),
                hasImages = (config.items || []).length > 0,
                $images,
                $upload;

            $content = $('<div class="imageoption-modal"></div>');

            if (hasImages) {
                // Left column: the stock images, scrolling on their own however many there are.
                $images = $('<div class="imageoption-modal__images"></div>').append($grid);
                $content.append($images);
            }

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

            if (config.uploadAllowed && $file.length) {
                // Right column: always in view, so it never gets pushed below a long list of images.
                $upload = $('<div class="imageoption-modal__upload"></div>')
                    .append($('<h4 class="imageoption-modal__upload-title"></h4>').text($t('Upload your own logo')))
                    .append($('<p class="imageoption-modal__upload-hint"></p>').text(
                        hasImages ? $t('Don\'t see yours? Upload a file instead.') : $t('Choose a file from your computer.')
                    ))
                    .append(
                        $('<button type="button" class="action primary imageoption-upload-button"></button>')
                            .append($('<span></span>').text($t('Choose File')))
                            .on('click', function () {
                                $file[0].click();
                            })
                    );

                if (config.uploadExtensions) {
                    $upload.append($('<p class="note"></p>').text(
                        $t('Compatible file extensions to upload') + ': ' + config.uploadExtensions
                    ));
                }
                $content.append($upload);
            }

            $content.toggleClass('imageoption-modal--two-columns', hasImages && !!$upload);

            modal({
                type: 'popup',
                responsive: true,
                innerScroll: false,
                title: hasImages ? $t('Choose an image') : $t('Upload your logo'),
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
            $value.val('');
            if ($file.length) {
                $file.val('');
            }
            $fileAction.val('save_new');
            render();
            $value.trigger('change');
            revalidate();
            $open.trigger('focus');
        });

        $file.on('change', function () {
            if (pickedFile()) {
                // a picked file replaces the chosen image and any kept upload
                $value.val('');
                $fileAction.val('save_new');
                if ($content) {
                    $content.modal('closeModal');
                }
            }
            render();
            revalidate();
        });

        render();
    };
});
