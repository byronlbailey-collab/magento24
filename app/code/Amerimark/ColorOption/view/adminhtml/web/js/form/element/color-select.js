/**
 * Copyright Amerimark. All rights reserved.
 */
define([
    'underscore',
    'Magento_Ui/js/form/element/select'
], function (_, Select) {
    'use strict';

    /**
     * Color chooser for one row of a Color option: a drop-down listing the colors of the row's color group, with a
     * swatch of the chosen color next to it. The list follows the row's "Color Group" field.
     */
    return Select.extend({
        defaults: {
            elementTmpl: 'Amerimark_ColorOption/form/element/color-select',
            colorsByGroup: {},
            groupId: '',
            hex: '',
            image: ''
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            var all = [];

            _.each(this.colorsByGroup, function (colors) {
                all = all.concat(colors);
            });
            // every color is listed at first so that a saved value is never discarded while the form loads
            this.options = all;
            this._super().observe(['groupId', 'hex', 'image']);

            return this;
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            var groupScope;

            this._super();
            // the group field is this field's sibling in the same row
            groupScope = String(this.dataScope).replace(/\.color_id$/, '.color_group_id');
            this.setLinks({
                groupId: this.provider + ':' + groupScope
            }, 'imports');
            this.groupId.subscribe(this.updateOptions.bind(this));
            this.value.subscribe(this.updateSwatch.bind(this));
            this.updateOptions(this.groupId());
            this.updateSwatch(this.value());

            return this;
        },

        /**
         * Offer only the colors of the chosen group; a row whose color is not in it loses its color.
         *
         * @param {*} groupId
         */
        updateOptions: function (groupId) {
            var colors = this.colorsByGroup[groupId] || [],
                current = this.value();

            this.setOptions(colors);

            if (current !== '' && current != null && !this.getOption(current)) {
                this.value('');
            }
            this.updateSwatch(this.value());
        },

        /**
         * Show the chosen color next to the drop-down.
         *
         * @param {*} value
         */
        updateSwatch: function (value) {
            var option = value === '' || value == null ? null : this.getOption(value);

            this.hex(option ? option.hex : '');
            this.image(option ? option.image : '');
        }
    });
});
