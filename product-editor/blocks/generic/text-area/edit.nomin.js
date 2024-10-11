"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.TextAreaBlockEdit = void 0;
const i18n_1 = require("@wordpress/i18n");
const block_templates_1 = require("@woocommerce/block-templates");
const element_1 = require("@wordpress/element");
const components_1 = require("@wordpress/components");
const compose_1 = require("@wordpress/compose");
const block_editor_1 = require("@wordpress/block-editor");
const classnames_1 = __importDefault(require("classnames"));
/**
 * Internal dependencies
 */
const toolbar_button_rtl_1 = require("./toolbar/toolbar-button-rtl");
const toolbar_button_alignment_1 = __importDefault(require("./toolbar/toolbar-button-alignment"));
const use_clear_selected_block_on_blur_1 = require("../../../hooks/use-clear-selected-block-on-blur");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const label_1 = require("../../../components/label/label");
function TextAreaBlockEdit({ attributes, setAttributes, context: { postType }, }) {
    const { property, label, placeholder, help, required, note, tooltip, disabled = false, align, allowedFormats, direction, mode = 'rich-text', } = attributes;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes, {
        className: 'wp-block-woocommerce-product-text-area-field',
        style: { direction },
    });
    const contentId = (0, compose_1.useInstanceId)(TextAreaBlockEdit, 'wp-block-woocommerce-product-content-field__content');
    const labelId = contentId.toString() + '__label';
    // `property` attribute is required.
    if (!property) {
        throw new Error((0, i18n_1.__)('Property attribute is required.', 'woocommerce'));
    }
    const [content, setContent] = (0, use_product_entity_prop_1.default)(property, {
        postType,
    });
    // This is a workaround to hide the toolbar when the block is blurred.
    // This is a temporary solution until using Gutenberg 18 with the
    // fix from https://github.com/WordPress/gutenberg/pull/59800
    const { handleBlur: hideToolbar } = (0, use_clear_selected_block_on_blur_1.useClearSelectedBlockOnBlur)();
    function setAlignment(value) {
        setAttributes({ align: value });
    }
    function changeDirection(value) {
        setAttributes({ direction: value });
    }
    const richTextRef = (0, element_1.useRef)(null);
    const textAreaRef = (0, element_1.useRef)(null);
    function focusRichText() {
        var _a;
        (_a = richTextRef.current) === null || _a === void 0 ? void 0 : _a.focus();
    }
    function focusTextArea() {
        var _a;
        (_a = textAreaRef.current) === null || _a === void 0 ? void 0 : _a.focus();
    }
    const blockControlsBlockProps = { group: 'block' };
    const isRichTextMode = mode === 'rich-text';
    const isPlainTextMode = mode === 'plain-text';
    return ((0, element_1.createElement)("div", { ...blockProps },
        isRichTextMode && ((0, element_1.createElement)(block_editor_1.BlockControls, { ...blockControlsBlockProps },
            (0, element_1.createElement)(toolbar_button_alignment_1.default, { align: align, setAlignment: setAlignment }),
            (0, element_1.createElement)(toolbar_button_rtl_1.RTLToolbarButton, { direction: direction, onChange: changeDirection }))),
        (0, element_1.createElement)(components_1.BaseControl, { id: contentId.toString(), label: (0, element_1.createElement)(label_1.Label, { label: label || '', labelId: labelId, required: required, note: note, tooltip: tooltip, onClick: isRichTextMode ? focusRichText : focusTextArea }), help: help },
            isRichTextMode && ((0, element_1.createElement)(block_editor_1.RichText, { ref: richTextRef, id: contentId.toString(), "aria-labelledby": labelId, identifier: "content", tagName: "p", value: content || '', onChange: setContent, "data-empty": Boolean(content), className: (0, classnames_1.default)('components-summary-control', {
                    [`has-text-align-${align}`]: align,
                }), dir: direction, allowedFormats: allowedFormats, placeholder: placeholder, required: required, "aria-required": required, readOnly: disabled, onBlur: hideToolbar })),
            isPlainTextMode && ((0, element_1.createElement)(components_1.TextareaControl, { ref: textAreaRef, "aria-labelledby": labelId, value: content || '', onChange: setContent, placeholder: placeholder, required: required, disabled: disabled, onBlur: hideToolbar })))));
}
exports.TextAreaBlockEdit = TextAreaBlockEdit;
