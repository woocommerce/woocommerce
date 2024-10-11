"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.NameBlockEdit = void 0;
/**
 * External dependencies
 */
const compose_1 = require("@wordpress/compose");
const data_1 = require("@wordpress/data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
const url_1 = require("@wordpress/url");
const block_templates_1 = require("@woocommerce/block-templates");
const classnames_1 = __importDefault(require("classnames"));
const components_1 = require("@wordpress/components");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const edit_product_link_modal_1 = require("../../../components/edit-product-link-modal");
const label_1 = require("../../../components/label/label");
const validation_context_1 = require("../../../contexts/validation-context");
const use_product_edits_1 = require("../../../hooks/use-product-edits");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const utils_1 = require("../../../utils");
function NameBlockEdit({ attributes, clientId, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    const { editEntityRecord, saveEntityRecord } = (0, data_1.useDispatch)('core');
    const { hasEdit } = (0, use_product_edits_1.useProductEdits)();
    const [showProductLinkEditModal, setShowProductLinkEditModal] = (0, element_1.useState)(false);
    const productId = (0, core_data_1.useEntityId)('postType', 'product');
    const product = (0, data_1.useSelect)((select) => 
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    select('core').getEditedEntityRecord('postType', 'product', productId));
    const [sku, setSku] = (0, core_data_1.useEntityProp)('postType', 'product', 'sku');
    const [name, setName] = (0, core_data_1.useEntityProp)('postType', 'product', 'name');
    const { prefix: permalinkPrefix, suffix: permalinkSuffix } = (0, utils_1.getPermalinkParts)(product);
    const { ref: nameRef, error: nameValidationError, validate: validateName, } = (0, validation_context_1.useValidation)('name', async function nameValidator() {
        if (!name || name === utils_1.AUTO_DRAFT_NAME) {
            return {
                message: (0, i18n_1.__)('Product name is required.', 'woocommerce'),
            };
        }
        if (name.length > 120) {
            return {
                message: (0, i18n_1.__)('Please enter a product name shorter than 120 characters.', 'woocommerce'),
            };
        }
    }, [name]);
    const setSkuIfEmpty = () => {
        if (sku || nameValidationError) {
            return;
        }
        setSku((0, url_1.cleanForSlug)(name));
    };
    const help = nameValidationError !== null && nameValidationError !== void 0 ? nameValidationError : (productId &&
        ['publish', 'draft'].includes(product.status) &&
        permalinkPrefix && ((0, element_1.createElement)("span", { className: "woocommerce-product-form__secondary-text product-details-section__product-link" },
        (0, i18n_1.__)('Product link', 'woocommerce'),
        ":\u00A0",
        (0, element_1.createElement)("a", { href: product.permalink, target: "_blank", rel: "noreferrer" },
            permalinkPrefix,
            product.slug || (0, url_1.cleanForSlug)(name),
            permalinkSuffix),
        (0, element_1.createElement)(components_1.Button, { variant: "link", onClick: () => setShowProductLinkEditModal(true) }, (0, i18n_1.__)('Edit', 'woocommerce')))));
    const nameControlId = (0, compose_1.useInstanceId)(components_1.BaseControl, 'product_name');
    // Select the block initially if it is set to autofocus.
    // (this does not get done automatically by focusing the input)
    const { selectBlock } = (0, data_1.useDispatch)('core/block-editor');
    (0, element_1.useEffect)(() => {
        if (attributes.autoFocus) {
            selectBlock(clientId);
        }
    }, []);
    const [featured, setFeatured] = (0, use_product_entity_prop_1.default)('featured');
    function handleSuffixClick() {
        setFeatured(!featured);
    }
    function renderFeaturedSuffix() {
        const markedText = (0, i18n_1.__)('Mark as featured', 'woocommerce');
        const unmarkedText = (0, i18n_1.__)('Unmark as featured', 'woocommerce');
        const tooltipText = featured ? unmarkedText : markedText;
        return ((0, element_1.createElement)(components_1.Tooltip, { text: tooltipText, position: "top center" }, featured ? ((0, element_1.createElement)(components_1.Button, { icon: icons_1.starFilled, "aria-label": unmarkedText, onClick: handleSuffixClick })) : ((0, element_1.createElement)(components_1.Button, { icon: icons_1.starEmpty, "aria-label": markedText, onClick: handleSuffixClick }))));
    }
    return ((0, element_1.createElement)(element_1.Fragment, null,
        (0, element_1.createElement)("div", { ...blockProps },
            (0, element_1.createElement)(components_1.BaseControl, { id: nameControlId, label: (0, element_1.createElement)(label_1.Label, { label: (0, i18n_1.__)('Name', 'woocommerce'), required: true }), className: (0, classnames_1.default)({
                    'has-error': nameValidationError,
                }), help: help },
                (0, element_1.createElement)(components_1.__experimentalInputControl, { id: nameControlId, ref: nameRef, name: "name", 
                    // eslint-disable-next-line jsx-a11y/no-autofocus
                    autoFocus: attributes.autoFocus, placeholder: (0, i18n_1.__)('e.g. 12 oz Coffee Mug', 'woocommerce'), onChange: setName, value: name && name !== utils_1.AUTO_DRAFT_NAME ? name : '', autoComplete: "off", "data-1p-ignore": true, onBlur: () => {
                        if (hasEdit('name')) {
                            setSkuIfEmpty();
                            validateName();
                        }
                    }, suffix: renderFeaturedSuffix() })),
            showProductLinkEditModal && ((0, element_1.createElement)(edit_product_link_modal_1.EditProductLinkModal, { permalinkPrefix: permalinkPrefix || '', permalinkSuffix: permalinkSuffix || '', product: product, onCancel: () => setShowProductLinkEditModal(false), onSaved: () => setShowProductLinkEditModal(false), saveHandler: async (updatedSlug) => {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    const { slug, permalink } = await saveEntityRecord('postType', 'product', {
                        id: product.id,
                        slug: updatedSlug,
                    });
                    if (slug && permalink) {
                        editEntityRecord('postType', 'product', product.id, {
                            slug,
                            permalink,
                        });
                        return {
                            slug,
                            permalink,
                        };
                    }
                } })))));
}
exports.NameBlockEdit = NameBlockEdit;
