/**
 * Copyright Amerimark. All rights reserved.
 */
define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    /**
     * Read-only text: the staff comment of the color chosen in the same row (set up on the color group).
     */
    return Abstract.extend({
        defaults: {
            elementTmpl: 'Amerimark_ColorOption/form/element/color-comment',
            commentsById: {},
            colorId: '',
            text: ''
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            return this._super().observe(['colorId', 'text']);
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            var colorScope;

            this._super();
            // the color field is this field's sibling in the same row
            colorScope = String(this.dataScope).replace(/\.color_comment$/, '.color_id');
            this.setLinks({
                colorId: this.provider + ':' + colorScope
            }, 'imports');
            this.colorId.subscribe(this.update.bind(this));
            this.update(this.colorId());

            return this;
        },

        /**
         * @param {*} colorId
         */
        update: function (colorId) {
            this.text(this.commentsById[colorId] || '');
        }
    });
});
