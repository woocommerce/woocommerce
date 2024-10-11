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
const label_1 = require("../../../components/label/label");
const validation_context_1 = require("../../../contexts/validation-context");
const use_currency_input_props_1 = require("../../../hooks/use-currency-input-props");
const sanitize_html_1 = require("../../../utils/sanitize-html");
function Edit({ attributes, clientId, context, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { label, help, isRequired, tooltip, disabled } = attributes;
    const [regularPrice, setRegularPrice] = (0, core_data_1.useEntityProp)('postType', context.postType || 'product', 'regular_price');
    const [salePrice] = (0, core_data_1.useEntityProp)('postType', context.postType || 'product', 'sale_price');
    const inputProps = (0, use_currency_input_props_1.useCurrencyInputProps)({
        value: regularPrice,
        onChange: setRegularPrice,
    });
    function renderHelp() {
        if (help) {
            return (0, element_1.createElement)("span", { dangerouslySetInnerHTML: (0, sanitize_html_1.sanitizeHTML)(help) });
        }
    }
    const regularPriceId = (0, compose_1.useInstanceId)(components_1.BaseControl, 'wp-block-woocommerce-product-regular-price-field');
    const { ref: regularPriceRef, error: regularPriceValidationError, validate: validateRegularPrice, } = (0, validation_context_1.useValidation)(`regular_price-${clientId}`, async function regularPriceValidator() {
        const listPrice = Number.parseFloat(regularPrice);
        if (listPrice) {
            if (listPrice < 0) {
                return {
                    message: (0, i18n_1.__)('Regular price must be greater than or equals to zero.', 'woocommerce'),
                };
            }
            if (salePrice &&
                listPrice <= Number.parseFloat(salePrice)) {
                return {
                    message: (0, i18n_1.__)('Regular price must be greater than the sale price.', 'woocommerce'),
                };
            }
        }
        else if (isRequired) {
            return {
                message: (0, i18n_1.sprintf)(
                /* translators: label of required field. */
                (0, i18n_1.__)('%s is required.', 'woocommerce'), label),
            };
        }
    }, [regularPrice, salePrice]);
    (0, element_1.useEffect)(() => {
        if (isRequired) {
            validateRegularPrice();
        }
    }, []);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_1.BaseControl, { id: regularPriceId, help: regularPriceValidationError
                ? regularPriceValidationError
                : renderHelp(), className: (0, classnames_1.default)({
                'has-error': regularPriceValidationError,
            }) },
            (0, element_1.createElement)(components_1.__experimentalInputControl, { ...inputProps, id: regularPriceId, name: 'regular_price', inputMode: "decimal", ref: regularPriceRef, label: tooltip ? ((0, element_1.createElement)(label_1.Label, { label: label, tooltip: tooltip })) : (label), disabled: disabled, onBlur: validateRegularPrice }))));
}
exports.Edit = Edit;
