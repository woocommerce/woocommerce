"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const block_templates_1 = require("@woocommerce/block-templates");
const tracks_1 = require("@woocommerce/tracks");
const components_1 = require("@wordpress/components");
const element_1 = require("@wordpress/element");
const block_editor_1 = require("@wordpress/block-editor");
/**
 * Internal dependencies
 */
const constants_1 = require("../../../constants");
const use_metabox_hidden_product_1 = require("../../../hooks/use-metabox-hidden-product");
const METABOX_HIDDEN_VALUE = 'postcustom';
function Edit({ attributes, }) {
    const { label, _templateBlockId } = attributes;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const innerBlockProps = (0, block_editor_1.useInnerBlocksProps)({
        className: 'wp-block-woocommerce-product-custom-fields-toggle-field__inner-blocks',
    }, {
        templateLock: 'all',
        renderAppender: false,
    });
    const { isLoading, metaboxhiddenProduct, saveMetaboxhiddenProduct } = (0, use_metabox_hidden_product_1.useMetaboxHiddenProduct)();
    const isChecked = (0, element_1.useMemo)(() => {
        return (metaboxhiddenProduct &&
            !metaboxhiddenProduct.some((value) => value === METABOX_HIDDEN_VALUE));
    }, [metaboxhiddenProduct]);
    async function handleChange(checked) {
        const values = checked
            ? metaboxhiddenProduct.filter((value) => value !== METABOX_HIDDEN_VALUE)
            : [...metaboxhiddenProduct, METABOX_HIDDEN_VALUE];
        (0, tracks_1.recordEvent)('product_custom_fields_toggle_click', {
            block_id: _templateBlockId,
            source: constants_1.TRACKS_SOURCE,
            metaboxhidden_product: values,
        });
        await saveMetaboxhiddenProduct(values);
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-custom-fields-toggle-field__content" },
            (0, element_1.createElement)(components_1.ToggleControl, { label: label, checked: isChecked, disabled: isLoading, onChange: handleChange }),
            isLoading && (0, element_1.createElement)(components_1.Spinner, null)),
        isChecked && (0, element_1.createElement)("div", { ...innerBlockProps })));
}
exports.Edit = Edit;
