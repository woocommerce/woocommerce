"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const i18n_1 = require("@wordpress/i18n");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const validation_context_1 = require("../../../contexts/validation-context");
const number_control_1 = require("../../../components/number-control");
const use_product_edits_1 = require("../../../hooks/use-product-edits");
function Edit({ attributes, context: { postType }, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { label, property, suffix, placeholder, help, min, max, required, tooltip, disabled, step, } = attributes;
    const [value, setValue] = (0, use_product_entity_prop_1.default)(property, {
        postType,
        fallbackValue: '',
    });
    const { hasEdit } = (0, use_product_edits_1.useProductEdits)();
    const { error, validate } = (0, validation_context_1.useValidation)(property, async function validator() {
        if (typeof min === 'number' &&
            value &&
            parseFloat(value) < min) {
            return {
                message: (0, i18n_1.sprintf)(
                // translators: %d is the minimum value of the number input.
                (0, i18n_1.__)('Value must be greater than or equal to %d', 'woocommerce'), min),
            };
        }
        if (typeof max === 'number' &&
            value &&
            parseFloat(value) > max) {
            return {
                message: (0, i18n_1.sprintf)(
                // translators: %d is the minimum value of the number input.
                (0, i18n_1.__)('Value must be less than or equal to %d', 'woocommerce'), min),
            };
        }
        if (required && !value) {
            return {
                message: (0, i18n_1.__)('This field is required.', 'woocommerce'),
            };
        }
    }, [value]);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(number_control_1.NumberControl, { label: label, onChange: setValue, value: value || '', help: help, suffix: suffix, placeholder: placeholder, error: error, onBlur: () => {
                if (hasEdit(property)) {
                    validate();
                }
            }, required: required, tooltip: tooltip, disabled: disabled, step: step, min: min, max: max })));
}
exports.Edit = Edit;
