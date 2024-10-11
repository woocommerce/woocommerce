"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const components_1 = require("@wordpress/components");
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const data_1 = require("@woocommerce/data");
const tracks_1 = require("@woocommerce/tracks");
const components_2 = require("@woocommerce/components");
const settings_1 = require("@woocommerce/settings");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const use_product_attributes_1 = require("../../../hooks/use-product-attributes");
const attribute_control_1 = require("../../../components/attribute-control");
const use_product_variations_helper_1 = require("../../../hooks/use-product-variations-helper");
const images_1 = require("./images");
function Edit({ attributes: blockAttributes, context: { postType, isInSelectedTab }, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(blockAttributes);
    const { generateProductVariations } = (0, use_product_variations_helper_1.useProductVariationsHelper)();
    const { updateUserPreferences, local_attributes_notice_dismissed_ids: dismissedNoticesIds = [], } = (0, data_1.useUserPreferences)();
    const [entityAttributes, setEntityAttributes] = (0, core_data_1.useEntityProp)('postType', 'product', 'attributes');
    const [entityDefaultAttributes, setEntityDefaultAttributes] = (0, core_data_1.useEntityProp)('postType', 'product', 'default_attributes');
    const productId = (0, core_data_1.useEntityId)('postType', postType);
    const { attributes, fetchAttributes, handleChange } = (0, use_product_attributes_1.useProductAttributes)({
        allAttributes: entityAttributes,
        isVariationAttributes: true,
        productId,
        onChange(values, defaultAttributes) {
            setEntityAttributes(values);
            setEntityDefaultAttributes(defaultAttributes);
            generateProductVariations(values, defaultAttributes);
        },
    });
    (0, element_1.useEffect)(() => {
        if (isInSelectedTab) {
            fetchAttributes();
        }
    }, [isInSelectedTab, entityAttributes]);
    const localAttributeNames = attributes
        .filter((attr) => attr.id === 0)
        .map((attr) => attr.name);
    let notice = '';
    if (localAttributeNames.length > 0 &&
        !(dismissedNoticesIds === null || dismissedNoticesIds === void 0 ? void 0 : dismissedNoticesIds.includes(productId))) {
        notice = (0, element_1.createInterpolateElement)((0, i18n_1.__)('Buyers can’t search or filter by <attributeNames /> to find the variations. Consider adding them again as <globalAttributeLink>global attributes</globalAttributeLink> to make them easier to discover.', 'woocommerce'), {
            attributeNames: ((0, element_1.createElement)("span", null, localAttributeNames.length === 2
                ? localAttributeNames.join((0, i18n_1.__)(' and ', 'woocommerce'))
                : localAttributeNames.join(', '))),
            globalAttributeLink: ((0, element_1.createElement)(components_2.Link, { href: (0, settings_1.getAdminLink)('edit.php?post_type=product&page=product_attributes'), type: "external", target: "_blank" })),
        });
    }
    function mapDefaultAttributes() {
        return attributes.map((attribute) => ({
            ...attribute,
            isDefault: entityDefaultAttributes.some((defaultAttribute) => defaultAttribute.id === attribute.id ||
                defaultAttribute.name === attribute.name),
        }));
    }
    function renderCustomEmptyState({ addAttribute, }) {
        return ((0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-variations-options-field__empty-state" },
            (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-variations-options-field__empty-state-image" },
                (0, element_1.createElement)(images_1.ProductTShirt, { className: "wp-block-woocommerce-product-variations-options-field__empty-state-image-product" }),
                (0, element_1.createElement)(images_1.ProductTShirt, { className: "wp-block-woocommerce-product-variations-options-field__empty-state-image-product" }),
                (0, element_1.createElement)(images_1.ProductTShirt, { className: "wp-block-woocommerce-product-variations-options-field__empty-state-image-product" })),
            (0, element_1.createElement)("p", { className: "wp-block-woocommerce-product-variations-options-field__empty-state-description" }, (0, i18n_1.__)('Sell your product in multiple variations like size or color.', 'woocommerce')),
            (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-variations-options-field__empty-state-actions" },
                (0, element_1.createElement)(components_1.Button, { variant: "primary", onClick: () => addAttribute() }, (0, i18n_1.__)('Add options', 'woocommerce')))));
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(attribute_control_1.AttributeControl, { value: (0, element_1.useMemo)(mapDefaultAttributes, [
                attributes,
                entityDefaultAttributes,
            ]), onAdd: () => {
                (0, tracks_1.recordEvent)('product_options_modal_add_button_click');
            }, onChange: handleChange, createNewAttributesAsGlobal: true, useRemoveConfirmationModal: true, onNoticeDismiss: () => updateUserPreferences({
                local_attributes_notice_dismissed_ids: [
                    ...dismissedNoticesIds,
                    productId,
                ],
            }), onAddAnother: () => {
                (0, tracks_1.recordEvent)('product_add_options_modal_add_another_option_button_click');
            }, onNewModalCancel: () => {
                (0, tracks_1.recordEvent)('product_options_modal_cancel_button_click');
            }, onNewModalOpen: () => {
                (0, tracks_1.recordEvent)('product_options_add_option');
            }, onRemoveItem: () => {
                (0, tracks_1.recordEvent)('product_add_options_modal_remove_option_button_click');
            }, onRemove: () => (0, tracks_1.recordEvent)('product_remove_option_confirmation_confirm_click'), onRemoveCancel: () => (0, tracks_1.recordEvent)('product_remove_option_confirmation_cancel_click'), renderCustomEmptyState: renderCustomEmptyState, disabledAttributeIds: entityAttributes
                .filter((attr) => !attr.variation)
                .map((attr) => attr.id), termsAutoSelection: "all", uiStrings: {
                notice,
                globalAttributeHelperMessage: '',
                customAttributeHelperMessage: '',
                newAttributeModalNotice: '',
                newAttributeModalTitle: (0, i18n_1.__)('Add variation options', 'woocommerce'),
                newAttributeModalDescription: (0, i18n_1.__)('Select from existing attributes or create new ones to add new variations for your product. You can change the order later.', 'woocommerce'),
                attributeRemoveLabel: (0, i18n_1.__)('Remove variation option', 'woocommerce'),
                attributeRemoveConfirmationModalMessage: (0, i18n_1.__)('If you continue, some variations of this product will be deleted and customers will no longer be able to purchase them.', 'woocommerce'),
            } })));
}
exports.Edit = Edit;
