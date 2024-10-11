"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.ProductDetailsSectionDescriptionBlockEdit = void 0;
/**
 * External dependencies
 */
const classnames_1 = __importDefault(require("classnames"));
const components_1 = require("@wordpress/components");
const data_1 = require("@wordpress/data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const icons = __importStar(require("@wordpress/icons"));
const block_templates_1 = require("@woocommerce/block-templates");
const navigation_1 = require("@woocommerce/navigation");
const tracks_1 = require("@woocommerce/tracks");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
const block_slot_fill_1 = require("../../../components/block-slot-fill");
const validation_context_1 = require("../../../contexts/validation-context");
const constants_1 = require("../../../constants");
const use_error_handler_1 = require("../../../hooks/use-error-handler");
const wooIcons = __importStar(require("../../../icons"));
const is_product_form_template_system_enabled_1 = __importDefault(require("../../../utils/is-product-form-template-system-enabled"));
const format_product_error_1 = require("../../../utils/format-product-error");
function ProductDetailsSectionDescriptionBlockEdit({ attributes, clientId, context: { selectedTab }, }) {
    var _a;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { getProductErrorMessageAndProps } = (0, use_error_handler_1.useErrorHandler)();
    const { productTemplates, productTemplate: selectedProductTemplate } = (0, data_1.useSelect)((select) => {
        const { getEditorSettings } = select('core/editor');
        return getEditorSettings();
    });
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const [supportedProductTemplates, unsupportedProductTemplates] = productTemplates.reduce(([supported, unsupported], productTemplate) => {
        if (productTemplate.isSelectableByUser) {
            if (productTemplate.layoutTemplateId) {
                supported.push(productTemplate);
            }
            else {
                unsupported.push(productTemplate);
            }
        }
        return [supported, unsupported];
    }, [[], []]);
    const productId = (0, core_data_1.useEntityId)('postType', 'product');
    const [productStatus] = (0, core_data_1.useEntityProp)('postType', 'product', 'status');
    const { validate } = (0, validation_context_1.useValidations)();
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    const { editEntityRecord, saveEditedEntityRecord, saveEntityRecord } = (0, data_1.useDispatch)('core');
    const { createSuccessNotice, createErrorNotice } = (0, data_1.useDispatch)('core/notices');
    const rootClientId = (0, data_1.useSelect)((select) => {
        const { getBlockRootClientId } = select('core/block-editor');
        return getBlockRootClientId(clientId);
    }, [clientId]);
    const [unsupportedProductTemplate, setUnsupportedProductTemplate] = (0, element_1.useState)();
    // Pull the product templates from the store.
    const productFormPosts = (0, data_1.useSelect)((sel) => {
        // Do not fetch product form posts if the feature is not enabled.
        if (!(0, is_product_form_template_system_enabled_1.default)()) {
            return [];
        }
        return (sel('core').getEntityRecords('postType', 'product_form', {
            per_page: -1,
        }) || []);
    }, []);
    const { isSaving } = (0, data_1.useSelect)((select) => {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        const { isSavingEntityRecord } = select('core');
        return {
            isSaving: isSavingEntityRecord('postType', 'product', productId),
        };
    }, [productId]);
    if (!rootClientId)
        return;
    function menuItemClickHandler(productTemplate, onClose) {
        return async function handleMenuItemClick() {
            var _a;
            try {
                (0, tracks_1.recordEvent)('product_template_selector_selected', {
                    source: constants_1.TRACKS_SOURCE,
                    selected_template: productTemplate.id,
                    unsupported_template: !productTemplate.layoutTemplateId,
                });
                if (!productTemplate.layoutTemplateId) {
                    setUnsupportedProductTemplate(productTemplate);
                    onClose();
                    return;
                }
                await validate(productTemplate.productData);
                const productMetaData = (_a = productTemplate.productData.meta_data) !== null && _a !== void 0 ? _a : [];
                await editEntityRecord('postType', 'product', productId, {
                    ...productTemplate.productData,
                    meta_data: [
                        ...productMetaData,
                        {
                            key: '_product_template_id',
                            value: productTemplate.id,
                        },
                    ],
                });
                await saveEditedEntityRecord('postType', 'product', productId, {
                    throwOnError: true,
                });
                createSuccessNotice((0, i18n_1.__)('Product type changed.', 'woocommerce'));
                (0, tracks_1.recordEvent)('product_template_changed', {
                    source: constants_1.TRACKS_SOURCE,
                    template: productTemplate.id,
                });
            }
            catch (error) {
                const { message, errorProps } = await getProductErrorMessageAndProps((0, format_product_error_1.formatProductError)(error, productStatus), selectedTab);
                createErrorNotice(message, errorProps);
            }
            onClose();
        };
    }
    function resolveIcon(iconId, alt) {
        if (!iconId)
            return undefined;
        const { Icon } = icons;
        let icon;
        if (/^https?:\/\//.test(iconId)) {
            icon = (0, element_1.createElement)("img", { src: iconId, alt: alt });
        }
        else {
            if (!(iconId in icons || iconId in wooIcons))
                return undefined;
            icon = icons[iconId] || wooIcons[iconId];
        }
        return (0, element_1.createElement)(Icon, { icon: icon, size: 24 });
    }
    /**
     * Returns a function that renders a MenuItem component.
     *
     * @param {Function} onClose - Function to close the dropdown.
     * @return {Function} Function that renders a MenuItem component.
     */
    function getMenuItem(onClose) {
        return function renderMenuItem(productTemplate) {
            var _a;
            const isSelected = (selectedProductTemplate === null || selectedProductTemplate === void 0 ? void 0 : selectedProductTemplate.id) === productTemplate.id;
            return ((0, element_1.createElement)(components_1.MenuItem, { key: productTemplate.id, info: (_a = productTemplate.description) !== null && _a !== void 0 ? _a : undefined, isSelected: isSelected, icon: isSelected
                    ? resolveIcon('check')
                    : resolveIcon(productTemplate.icon, productTemplate.title), iconPosition: "left", role: "menuitemradio", onClick: menuItemClickHandler(productTemplate, onClose), className: (0, classnames_1.default)({
                    'components-menu-item__button--selected': isSelected,
                }) }, productTemplate.title));
        };
    }
    async function handleModalChangeClick() {
        var _a, _b;
        try {
            if (isSaving)
                return;
            const { id: productTemplateId, productData } = unsupportedProductTemplate;
            await validate(productData);
            const product = (_a = (await saveEditedEntityRecord('postType', 'product', productId, {
                throwOnError: true,
            }))) !== null && _a !== void 0 ? _a : { id: productId };
            const productMetaData = (_b = productData === null || productData === void 0 ? void 0 : productData.meta_data) !== null && _b !== void 0 ? _b : [];
            // Avoiding to save some changes that are not supported by the current product template.
            // So in this case those changes are saved directly to the server.
            await saveEntityRecord('postType', 'product', {
                ...product,
                ...productData,
                meta_data: [
                    ...productMetaData,
                    {
                        key: '_product_template_id',
                        value: productTemplateId,
                    },
                ],
            }, 
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            {
                throwOnError: true,
            });
            createSuccessNotice((0, i18n_1.__)('Product type changed.', 'woocommerce'));
            (0, tracks_1.recordEvent)('product_template_changed', {
                source: constants_1.TRACKS_SOURCE,
                template: productTemplateId,
            });
            // Let the server manage the redirection when the product is not supported
            // by the product editor.
            window.location.href = (0, navigation_1.getNewPath)({}, `/product/${productId}`);
        }
        catch (error) {
            const { message, errorProps } = await getProductErrorMessageAndProps((0, format_product_error_1.formatProductError)(error, productStatus), selectedTab);
            createErrorNotice(message, errorProps);
        }
    }
    function toggleButtonClickHandler(isOpen, onToggle) {
        return function onClick() {
            onToggle();
            if (!isOpen) {
                (0, tracks_1.recordEvent)('product_template_selector_open', {
                    source: constants_1.TRACKS_SOURCE,
                    supported_templates: supportedProductTemplates.map((productTemplate) => productTemplate.id),
                    unsupported_template: unsupportedProductTemplates.map((productTemplate) => productTemplate.id),
                });
            }
        };
    }
    return ((0, element_1.createElement)(block_slot_fill_1.BlockFill, { name: "section-description", slotContainerBlockName: "woocommerce/product-section" },
        (0, element_1.createElement)("div", { ...blockProps },
            (0, element_1.createElement)("p", null, (0, element_1.createInterpolateElement)(
            /* translators: <ProductTemplate />: the product template. */
            (0, i18n_1.__)('This is a <ProductTemplate />.', 'woocommerce'), {
                ProductTemplate: ((0, element_1.createElement)("span", null, (_a = selectedProductTemplate === null || selectedProductTemplate === void 0 ? void 0 : selectedProductTemplate.title) === null || _a === void 0 ? void 0 : _a.toLowerCase())),
            })),
            (0, element_1.createElement)(components_1.Dropdown
            // @ts-expect-error Property does exists
            , { 
                // @ts-expect-error Property does exists
                focusOnMount: true, popoverProps: {
                    placement: 'bottom-start',
                }, renderToggle: ({ isOpen, onToggle }) => ((0, element_1.createElement)(components_1.Button, { "aria-expanded": isOpen, variant: "link", onClick: toggleButtonClickHandler(isOpen, onToggle) },
                    (0, element_1.createElement)("span", null, (0, i18n_1.__)('Change product type', 'woocommerce')))), renderContent: ({ onClose }) => ((0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-details-section-description__dropdown components-dropdown-menu__menu" },
                    (0, element_1.createElement)(components_1.MenuGroup, null, supportedProductTemplates.map(getMenuItem(onClose))),
                    (0, is_product_form_template_system_enabled_1.default)() && ((0, element_1.createElement)(components_1.MenuGroup, null, productFormPosts.map((formPost) => ((0, element_1.createElement)(components_1.MenuItem, { key: formPost.id, icon: resolveIcon('external'), info: formPost.excerpt.raw, iconPosition: "left", onClick: onClose }, formPost.title.rendered))))),
                    unsupportedProductTemplates.length > 0 && ((0, element_1.createElement)(components_1.MenuGroup, null,
                        (0, element_1.createElement)(components_1.Dropdown
                        // @ts-expect-error Property does exists
                        , { 
                            // @ts-expect-error Property does exists
                            popoverProps: {
                                placement: 'right-start',
                            }, renderToggle: ({ isOpen, onToggle, }) => ((0, element_1.createElement)(components_1.MenuItem, { "aria-expanded": isOpen, icon: resolveIcon('chevronRight'), iconPosition: "right", onClick: onToggle },
                                (0, element_1.createElement)("span", null, (0, i18n_1.__)('More', 'woocommerce')))), renderContent: () => ((0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-details-section-description__dropdown components-dropdown-menu__menu" },
                                (0, element_1.createElement)(components_1.MenuGroup, null, unsupportedProductTemplates.map(getMenuItem(onClose))))) }))))) }),
            Boolean(unsupportedProductTemplate) && ((0, element_1.createElement)(components_1.Modal, { title: (0, i18n_1.__)('Change product type?', 'woocommerce'), className: "wp-block-woocommerce-product-details-section-description__modal", onRequestClose: () => {
                    setUnsupportedProductTemplate(undefined);
                } },
                (0, element_1.createElement)("p", null,
                    (0, element_1.createElement)("b", null, (0, i18n_1.__)('This product type isn’t supported by the updated product editing experience yet.', 'woocommerce'))),
                (0, element_1.createElement)("p", null, (0, i18n_1.__)('You’ll be taken to the classic editing screen that isn’t optimized for commerce but offers advanced functionality and supports all extensions.', 'woocommerce')),
                (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-details-section-description__modal-actions" },
                    (0, element_1.createElement)(components_1.Button, { variant: "secondary", "aria-disabled": isSaving, onClick: () => {
                            if (isSaving)
                                return;
                            setUnsupportedProductTemplate(undefined);
                        } }, (0, i18n_1.__)('Cancel', 'woocommerce')),
                    (0, element_1.createElement)(components_1.Button, { variant: "primary", isBusy: isSaving, "aria-disabled": isSaving, onClick: handleModalChangeClick }, (0, i18n_1.__)('Change', 'woocommerce'))))))));
}
exports.ProductDetailsSectionDescriptionBlockEdit = ProductDetailsSectionDescriptionBlockEdit;
