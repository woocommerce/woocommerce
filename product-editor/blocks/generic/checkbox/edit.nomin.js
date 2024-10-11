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
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const checkbox_control_1 = require("../../../components/checkbox-control");
function Edit({ attributes, context: { postType }, }) {
    const { property, title, label, tooltip, checkedValue, uncheckedValue, disabled, } = attributes;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [value, setValue] = (0, use_product_entity_prop_1.default)(property, {
        postType,
        fallbackValue: false,
    });
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(checkbox_control_1.Checkbox, { value: value || false, onChange: setValue, label: label || '', title: title, tooltip: tooltip, checkedValue: checkedValue, uncheckedValue: uncheckedValue, disabled: disabled })));
}
exports.Edit = Edit;
