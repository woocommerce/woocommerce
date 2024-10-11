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
const components_1 = require("@wordpress/components");
const element_1 = require("@wordpress/element");
/**
 * Internal dependencies
 */
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const sanitize_html_1 = require("../../../utils/sanitize-html");
const label_1 = require("../../../components/label/label");
function Edit({ attributes, context: { postType }, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { property, label, note, placeholder, help, tooltip, disabled, options, multiple, } = attributes;
    const [value, setValue] = (0, use_product_entity_prop_1.default)(property, {
        postType,
        fallbackValue: '',
    });
    function renderHelp() {
        if (help) {
            return (0, element_1.createElement)("span", { dangerouslySetInnerHTML: (0, sanitize_html_1.sanitizeHTML)(help) });
        }
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_1.SelectControl, { value: value, disabled: disabled, label: (0, element_1.createElement)(label_1.Label, { label: label, note: note, tooltip: tooltip }), onChange: setValue, help: renderHelp(), placeholder: placeholder, options: options, multiple: multiple })));
}
exports.Edit = Edit;
