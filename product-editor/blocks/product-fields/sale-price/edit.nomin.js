"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const classnames_1 = __importDefault(require("classnames"));
const block_templates_1 = require("@woocommerce/block-templates");
const compose_1 = require("@wordpress/compose");
const core_data_1 = require("@wordpress/core-data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const components_1 = require("@wordpress/components");
/**
 * Internal dependencies
 */
const validation_context_1 = require("../../../contexts/validation-context");
const use_currency_input_props_1 = require("../../../hooks/use-currency-input-props");
const label_1 = require("../../../components/label/label");
function Edit({ attributes, clientId, context, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { label, help, tooltip, disabled } = attributes;
    const [regularPrice] = (0, core_data_1.useEntityProp)('postType', context.postType || 'product', 'regular_price');
    const [salePrice, setSalePrice] = (0, core_data_1.useEntityProp)('postType', context.postType || 'product', 'sale_price');
    const inputProps = (0, use_currency_input_props_1.useCurrencyInputProps)({
        value: salePrice,
        onChange: setSalePrice,
    });
    const salePriceId = (0, compose_1.useInstanceId)(components_1.BaseControl, 'wp-block-woocommerce-product-sale-price-field');
    const { ref: salePriceRef, error: salePriceValidationError, validate: validateSalePrice, } = (0, validation_context_1.useValidation)(`sale-price-${clientId}`, async function salePriceValidator() {
        if (salePrice) {
            if (Number.parseFloat(salePrice) < 0) {
                return {
                    message: (0, i18n_1.__)('Sale price must be greater than or equals to zero.', 'woocommerce'),
                };
            }
            const listPrice = Number.parseFloat(regularPrice);
            if (!listPrice ||
                listPrice <= Number.parseFloat(salePrice)) {
                return {
                    message: (0, i18n_1.__)('Sale price must be lower than the regular price.', 'woocommerce'),
                };
            }
        }
    }, [regularPrice, salePrice]);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_1.BaseControl, { id: salePriceId, help: salePriceValidationError ? salePriceValidationError : help, className: (0, classnames_1.default)({
                'has-error': salePriceValidationError,
            }) },
            (0, element_1.createElement)(components_1.__experimentalInputControl, { ...inputProps, id: salePriceId, name: 'sale_price', inputMode: "decimal", ref: salePriceRef, label: tooltip ? ((0, element_1.createElement)(label_1.Label, { label: label, tooltip: tooltip })) : (label), disabled: disabled, onBlur: validateSalePrice }))));
}
exports.Edit = Edit;
