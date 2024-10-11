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
/**
 * Internal dependencies
 */
const radio_field_1 = require("../../../components/radio-field");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
function Edit({ attributes, context: { postType }, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { description, options, property, title, disabled } = attributes;
    const [value, setValue] = (0, use_product_entity_prop_1.default)(property, {
        postType,
        fallbackValue: '',
    });
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(radio_field_1.RadioField, { title: title, description: description, selected: value, options: options, onChange: (selected) => setValue(selected || ''), disabled: disabled })));
}
exports.Edit = Edit;
