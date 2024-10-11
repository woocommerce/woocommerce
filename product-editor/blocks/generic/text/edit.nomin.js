"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const block_templates_1 = require("@woocommerce/block-templates");
const compose_1 = require("@wordpress/compose");
const components_1 = require("@woocommerce/components");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
/**
 * Internal dependencies
 */
const text_control_1 = require("../../../components/text-control");
const validation_context_1 = require("../../../contexts/validation-context");
const use_product_edits_1 = require("../../../hooks/use-product-edits");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
function Edit({ attributes, context: { postType }, }) {
    var _a;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { property, label, placeholder, required, pattern, minLength, maxLength, min, max, help, tooltip, disabled, type, suffix, } = attributes;
    const [value, setValue] = (0, use_product_entity_prop_1.default)(property, {
        postType,
        fallbackValue: '',
    });
    const { hasEdit } = (0, use_product_edits_1.useProductEdits)();
    const inputRef = (0, element_1.useRef)(null);
    const { error, validate, ref: inputValidatorRef, } = (0, validation_context_1.useValidation)(property, async function validator() {
        var _a, _b, _c, _d, _e, _f;
        if (!inputRef.current)
            return;
        const input = inputRef.current;
        let customErrorMessage = '';
        if (input.validity.typeMismatch) {
            customErrorMessage =
                (_a = type === null || type === void 0 ? void 0 : type.message) !== null && _a !== void 0 ? _a : (0, i18n_1.__)('Invalid value for the field.', 'woocommerce');
        }
        if (input.validity.valueMissing) {
            customErrorMessage =
                typeof required === 'string'
                    ? required
                    : (0, i18n_1.__)('This field is required.', 'woocommerce');
        }
        if (input.validity.patternMismatch) {
            customErrorMessage =
                (_b = pattern === null || pattern === void 0 ? void 0 : pattern.message) !== null && _b !== void 0 ? _b : (0, i18n_1.__)('Invalid value for the field.', 'woocommerce');
        }
        if (input.validity.tooShort) {
            // eslint-disable-next-line @wordpress/valid-sprintf
            customErrorMessage = (0, i18n_1.sprintf)((_c = minLength === null || minLength === void 0 ? void 0 : minLength.message) !== null && _c !== void 0 ? _c : 
            /* translators: %d: minimum length */
            (0, i18n_1.__)('The minimum length of the field is %d', 'woocommerce'), minLength === null || minLength === void 0 ? void 0 : minLength.value);
        }
        if (input.validity.tooLong) {
            // eslint-disable-next-line @wordpress/valid-sprintf
            customErrorMessage = (0, i18n_1.sprintf)((_d = maxLength === null || maxLength === void 0 ? void 0 : maxLength.message) !== null && _d !== void 0 ? _d : 
            /* translators: %d: maximum length */
            (0, i18n_1.__)('The maximum length of the field is %d', 'woocommerce'), maxLength === null || maxLength === void 0 ? void 0 : maxLength.value);
        }
        if (input.validity.rangeUnderflow) {
            // eslint-disable-next-line @wordpress/valid-sprintf
            customErrorMessage = (0, i18n_1.sprintf)((_e = min === null || min === void 0 ? void 0 : min.message) !== null && _e !== void 0 ? _e : 
            /* translators: %d: minimum length */
            (0, i18n_1.__)('The minimum value of the field is %d', 'woocommerce'), min === null || min === void 0 ? void 0 : min.value);
        }
        if (input.validity.rangeOverflow) {
            // eslint-disable-next-line @wordpress/valid-sprintf
            customErrorMessage = (0, i18n_1.sprintf)((_f = max === null || max === void 0 ? void 0 : max.message) !== null && _f !== void 0 ? _f : 
            /* translators: %d: maximum length */
            (0, i18n_1.__)('The maximum value of the field is %d', 'woocommerce'), max === null || max === void 0 ? void 0 : max.value);
        }
        input.setCustomValidity(customErrorMessage);
        if (!input.validity.valid) {
            return {
                message: customErrorMessage,
            };
        }
    }, [type, required, pattern, minLength, maxLength, min, max, value]);
    function getSuffix() {
        if (!suffix || !value || !inputRef.current)
            return;
        const isValidUrl = inputRef.current.type === 'url' &&
            !inputRef.current.validity.typeMismatch;
        if (suffix === true && isValidUrl) {
            return ((0, element_1.createElement)(components_1.Link, { type: "external", href: value, target: "_blank", rel: "noreferrer", className: "wp-block-woocommerce-product-text-field__suffix-link" },
                (0, element_1.createElement)(icons_1.Icon, { icon: icons_1.external, size: 20 })));
        }
        return typeof suffix === 'string' ? suffix : undefined;
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(text_control_1.TextControl, { ref: (0, compose_1.useMergeRefs)([inputRef, inputValidatorRef]), type: (_a = type === null || type === void 0 ? void 0 : type.value) !== null && _a !== void 0 ? _a : 'text', value: value, disabled: disabled, label: label, onChange: setValue, onBlur: () => {
                if (hasEdit(property)) {
                    validate();
                }
            }, error: error, help: help, placeholder: placeholder, tooltip: tooltip, suffix: getSuffix(), required: Boolean(required), pattern: pattern === null || pattern === void 0 ? void 0 : pattern.value, minLength: minLength === null || minLength === void 0 ? void 0 : minLength.value, maxLength: maxLength === null || maxLength === void 0 ? void 0 : maxLength.value, min: min === null || min === void 0 ? void 0 : min.value, max: max === null || max === void 0 ? void 0 : max.value })));
}
exports.Edit = Edit;
