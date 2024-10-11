"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const compose_1 = require("@wordpress/compose");
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const components_1 = require("@wordpress/components");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
const validation_context_1 = require("../../../contexts/validation-context");
/**
 * Internal dependencies
 */
function Edit({ attributes, context, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [sku, setSku] = (0, core_data_1.useEntityProp)('postType', context.postType, 'sku');
    const { ref: skuRef } = (0, validation_context_1.useValidation)('sku', async function skuValidator() {
        return undefined;
    }, [sku]);
    const inputControlId = (0, compose_1.useInstanceId)(components_1.BaseControl, 'product_sku');
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_1.BaseControl, { id: inputControlId, className: "woocommerce-product-form_inventory-sku", label: (0, element_1.createInterpolateElement)((0, i18n_1.__)('Sku <description />', 'woocommerce'), {
                description: ((0, element_1.createElement)("span", { className: "woocommerce-product-form__optional-input" }, (0, i18n_1.__)('(STOCK KEEPING UNIT)', 'woocommerce'))),
            }) },
            (0, element_1.createElement)(components_1.__experimentalInputControl, { ref: skuRef, id: inputControlId, name: 'woocommerce-product-sku', onChange: setSku, value: sku || '', disabled: attributes.disabled }))));
}
exports.Edit = Edit;
