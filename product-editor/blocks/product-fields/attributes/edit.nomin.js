"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AttributesBlockEdit = void 0;
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const i18n_1 = require("@wordpress/i18n");
const tracks_1 = require("@woocommerce/tracks");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const attribute_control_1 = require("../../../components/attribute-control");
const use_product_attributes_1 = require("../../../hooks/use-product-attributes");
function AttributesBlockEdit({ attributes, context: { isInSelectedTab }, }) {
    const [entityAttributes, setEntityAttributes] = (0, core_data_1.useEntityProp)('postType', 'product', 'attributes');
    const productId = (0, core_data_1.useEntityId)('postType', 'product');
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { attributes: attributeList, fetchAttributes, handleChange, } = (0, use_product_attributes_1.useProductAttributes)({
        allAttributes: entityAttributes,
        onChange: setEntityAttributes,
        productId,
    });
    (0, element_1.useEffect)(() => {
        if (isInSelectedTab) {
            fetchAttributes();
        }
    }, [entityAttributes, isInSelectedTab]);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(attribute_control_1.AttributeControl, { value: attributeList, disabledAttributeIds: entityAttributes
                .filter((attr) => !!attr.variation)
                .map((attr) => attr.id), uiStrings: {
                disabledAttributeMessage: (0, i18n_1.__)('Already used in Variations', 'woocommerce'),
            }, onAdd: () => {
                (0, tracks_1.recordEvent)('product_add_attributes_modal_add_button_click');
            }, onChange: handleChange, onNewModalCancel: () => {
                (0, tracks_1.recordEvent)('product_add_attributes_modal_cancel_button_click');
            }, onNewModalOpen: () => {
                if (!attributeList.length) {
                    (0, tracks_1.recordEvent)('product_add_first_attribute_button_click');
                    return;
                }
                (0, tracks_1.recordEvent)('product_add_attribute_button');
            }, onAddAnother: () => {
                (0, tracks_1.recordEvent)('product_add_attributes_modal_add_another_attribute_button_click');
            }, onRemoveItem: () => {
                (0, tracks_1.recordEvent)('product_add_attributes_modal_remove_attribute_button_click');
            }, onRemove: () => (0, tracks_1.recordEvent)('product_remove_attribute_confirmation_confirm_click'), onRemoveCancel: () => (0, tracks_1.recordEvent)('product_remove_attribute_confirmation_cancel_click'), termsAutoSelection: "first", defaultVisibility: true })));
}
exports.AttributesBlockEdit = AttributesBlockEdit;
