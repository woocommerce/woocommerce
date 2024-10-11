"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = exports.DEFAULT_SHIPPING_CLASS_OPTIONS = void 0;
/**
 * External dependencies
 */
const block_templates_1 = require("@woocommerce/block-templates");
const components_1 = require("@woocommerce/components");
const data_1 = require("@woocommerce/data");
const navigation_1 = require("@woocommerce/navigation");
const tracks_1 = require("@woocommerce/tracks");
const components_2 = require("@wordpress/components");
const compose_1 = require("@wordpress/compose");
const data_2 = require("@wordpress/data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const core_data_1 = require("@wordpress/core-data");
const components_3 = require("../../../components");
const constants_1 = require("../../../constants");
exports.DEFAULT_SHIPPING_CLASS_OPTIONS = [
    { value: '', label: (0, i18n_1.__)('No shipping class', 'woocommerce') },
    {
        value: constants_1.ADD_NEW_SHIPPING_CLASS_OPTION_VALUE,
        label: (0, i18n_1.__)('Add new shipping class', 'woocommerce'),
    },
];
function mapShippingClassToSelectOption(shippingClasses) {
    return shippingClasses.map(({ slug, name }) => ({
        value: slug,
        label: name,
    }));
}
/*
 * Query to fetch shipping classes.
 */
const shippingClassRequestQuery = {};
function extractDefaultShippingClassFromProduct(categories, shippingClasses) {
    const category = categories === null || categories === void 0 ? void 0 : categories.find(({ slug }) => slug !== 'uncategorized');
    if (category &&
        !(shippingClasses === null || shippingClasses === void 0 ? void 0 : shippingClasses.some(({ slug }) => slug === category.slug))) {
        return {
            name: category.name,
            slug: category.slug,
        };
    }
}
function Edit({ attributes, context: { postType, isInSelectedTab }, }) {
    const [showShippingClassModal, setShowShippingClassModal] = (0, element_1.useState)(false);
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { createProductShippingClass } = (0, data_2.useDispatch)(data_1.EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME);
    const { createErrorNotice } = (0, data_2.useDispatch)('core/notices');
    const [categories] = (0, core_data_1.useEntityProp)('postType', postType, 'categories');
    const [shippingClass, setShippingClass] = (0, core_data_1.useEntityProp)('postType', postType, 'shipping_class');
    const [virtual] = (0, core_data_1.useEntityProp)('postType', postType, 'virtual');
    function handleShippingClassServerError(error) {
        let message = (0, i18n_1.__)('We couldn’t add this shipping class. Try again in a few seconds.', 'woocommerce');
        if (error.code === 'term_exists') {
            message = (0, i18n_1.__)('A shipping class with that slug already exists.', 'woocommerce');
        }
        createErrorNotice(message, {
            explicitDismiss: true,
        });
        throw error;
    }
    const { shippingClasses } = (0, data_2.useSelect)((select) => {
        const { getProductShippingClasses } = select(data_1.EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME);
        return {
            shippingClasses: (isInSelectedTab &&
                getProductShippingClasses(shippingClassRequestQuery)) ||
                [],
        };
    }, [isInSelectedTab]);
    const shippingClassControlId = (0, compose_1.useInstanceId)(components_2.BaseControl, 'wp-block-woocommerce-product-shipping-class-field');
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)("div", { className: "wp-block-columns" },
            (0, element_1.createElement)("div", { className: "wp-block-column" },
                (0, element_1.createElement)(components_2.SelectControl, { id: shippingClassControlId, name: "shipping_class", value: shippingClass, onChange: (value) => {
                        if (value === constants_1.ADD_NEW_SHIPPING_CLASS_OPTION_VALUE) {
                            setShowShippingClassModal(true);
                            return;
                        }
                        setShippingClass(value);
                    }, label: (0, i18n_1.__)('Shipping class', 'woocommerce'), options: [
                        ...exports.DEFAULT_SHIPPING_CLASS_OPTIONS,
                        ...mapShippingClassToSelectOption(shippingClasses !== null && shippingClasses !== void 0 ? shippingClasses : []),
                    ], disabled: attributes.disabled || virtual, help: (0, element_1.createInterpolateElement)((0, i18n_1.__)('Manage shipping classes and rates in <Link>global settings</Link>.', 'woocommerce'), {
                        Link: ((0, element_1.createElement)(components_1.Link, { href: (0, navigation_1.getNewPath)({
                                tab: 'shipping',
                                section: 'classes',
                            }, '', {}, 'wc-settings'), target: "_blank", type: "external", onClick: () => {
                                (0, tracks_1.recordEvent)('product_shipping_global_settings_link_click');
                            } },
                            (0, element_1.createElement)(element_1.Fragment, null))),
                    }) })),
            (0, element_1.createElement)("div", { className: "wp-block-column" })),
        showShippingClassModal && ((0, element_1.createElement)(components_3.AddNewShippingClassModal, { shippingClass: extractDefaultShippingClassFromProduct(categories, shippingClasses), onAdd: (shippingClassValues) => createProductShippingClass(shippingClassValues, {
                optimisticQueryUpdate: shippingClassRequestQuery,
            })
                .then((value) => {
                (0, tracks_1.recordEvent)('product_new_shipping_class_modal_add_button_click');
                setShippingClass(value.slug);
                return value;
            })
                .catch(handleShippingClassServerError), onCancel: () => setShowShippingClassModal(false) }))));
}
exports.Edit = Edit;
