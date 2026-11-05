/**
 * Copyright Amerimark. All rights reserved.
 */
define([
    'uiRegistry',
    'Magento_Catalog/js/custom-options-type'
], function (registry, OptionType) {
    'use strict';

    /**
     * The Customizable Options "Option Type" selector. The core one shows and hides the fields of each option type
     * from its groupsConfig; this adds the Color option's colors grid, which the core lookup cannot find (like the
     * drop-down values grid it is addressed by the option's data scope).
     */
    return OptionType.extend({
        /**
         * @inheritdoc
         */
        updateComponents: function (currentValue, isInitialization) {
            var visible = this.valuesMap[currentValue] === 'color_swatch',
                query = 'ns=' + this.ns + ', dataScope=' + this.parentScope + ', index=color_values';

            this._super(currentValue, isInitialization);

            if (isInitialization) {
                registry.async(query)(function (grid) {
                    grid.visible(visible);
                });
            } else if (registry.get(query)) {
                registry.get(query).visible(visible);
            }

            return this;
        }
    });
});
