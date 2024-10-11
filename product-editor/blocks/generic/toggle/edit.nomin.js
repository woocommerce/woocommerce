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
const components_1 = require("@wordpress/components");
const block_templates_1 = require("@woocommerce/block-templates");
const tracks_1 = require("@woocommerce/tracks");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
const sanitize_html_1 = require("../../../utils/sanitize-html");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const constants_1 = require("../../../constants");
function Edit({ attributes, context: { postType }, }) {
    var _a, _b, _c;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { _templateBlockId, label, property, disabled, disabledCopy, checkedValue, uncheckedValue, } = attributes;
    const [value, setValue] = (0, use_product_entity_prop_1.default)(property, {
        postType,
        fallbackValue: false,
    });
    const productId = (0, core_data_1.useEntityId)('postType', postType);
    const [parentId] = (0, core_data_1.useEntityProp)('postType', postType, 'parent_id');
    function isChecked() {
        if (checkedValue !== undefined) {
            return checkedValue === value;
        }
        return value;
    }
    function handleChange(checked) {
        (0, tracks_1.recordEvent)('product_toggle_click', {
            block_id: _templateBlockId,
            source: constants_1.TRACKS_SOURCE,
            product_id: parentId > 0 ? parentId : productId,
        });
        if (checked) {
            setValue(checkedValue !== undefined ? checkedValue : checked);
        }
        else {
            setValue(uncheckedValue !== undefined ? uncheckedValue : checked);
        }
    }
    let help = null;
    // Default help text.
    if (attributes === null || attributes === void 0 ? void 0 : attributes.help) {
        help = (0, element_1.createElement)('div', {
            dangerouslySetInnerHTML: {
                __html: (_a = (0, sanitize_html_1.sanitizeHTML)(attributes.help)) === null || _a === void 0 ? void 0 : _a.__html,
            },
        });
    }
    /*
     * Redefine the help text when:
     * - The checked help text is defined
     * - The toggle is checked
     */
    if ((attributes === null || attributes === void 0 ? void 0 : attributes.checkedHelp) && isChecked()) {
        help = (0, element_1.createElement)('div', {
            dangerouslySetInnerHTML: {
                __html: (_b = (0, sanitize_html_1.sanitizeHTML)(attributes.checkedHelp)) === null || _b === void 0 ? void 0 : _b.__html,
            },
        });
    }
    /*
     * Redefine the help text when:
     * - The unchecked help text is defined
     * - The toggle is unchecked
     */
    if ((attributes === null || attributes === void 0 ? void 0 : attributes.uncheckedHelp) && !isChecked()) {
        help = (0, element_1.createElement)('div', {
            dangerouslySetInnerHTML: {
                __html: (_c = (0, sanitize_html_1.sanitizeHTML)(attributes.uncheckedHelp)) === null || _c === void 0 ? void 0 : _c.__html,
            },
        });
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_1.ToggleControl, { label: label, checked: isChecked(), 
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore disabled prop exists
            disabled: disabled, onChange: handleChange, help: help }),
        disabled && ((0, element_1.createElement)("p", { className: "wp-block-woocommerce-product-toggle__disable-copy", dangerouslySetInnerHTML: (0, sanitize_html_1.sanitizeHTML)(disabledCopy) }))));
}
exports.Edit = Edit;
