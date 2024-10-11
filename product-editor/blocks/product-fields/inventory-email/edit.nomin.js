"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const block_templates_1 = require("@woocommerce/block-templates");
const components_1 = require("@woocommerce/components");
const element_1 = require("@wordpress/element");
const settings_1 = require("@woocommerce/settings");
const compose_1 = require("@wordpress/compose");
const components_2 = require("@wordpress/components");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const validation_context_1 = require("../../../contexts/validation-context");
function Edit({ attributes, clientId, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const notifyLowStockAmount = (0, settings_1.getSetting)('notifyLowStockAmount', 2);
    const [lowStockAmount, setLowStockAmount] = (0, core_data_1.useEntityProp)('postType', 'product', 'low_stock_amount');
    const id = (0, compose_1.useInstanceId)(components_2.BaseControl, 'low_stock_amount');
    const { ref: lowStockAmountRef, error: lowStockAmountValidationError, validate: validateLowStockAmount, } = (0, validation_context_1.useValidation)(`low_stock_amount-${clientId}`, async function stockQuantityValidator() {
        if (lowStockAmount && lowStockAmount < 0) {
            return {
                message: (0, i18n_1.__)('This field must be a positive number.', 'woocommerce'),
            };
        }
    }, [lowStockAmount]);
    return ((0, element_1.createElement)(element_1.Fragment, null,
        (0, element_1.createElement)("div", { ...blockProps },
            (0, element_1.createElement)("div", { className: "wp-block-columns" },
                (0, element_1.createElement)("div", { className: "wp-block-column" },
                    (0, element_1.createElement)(components_2.BaseControl, { id: id, label: (0, i18n_1.__)('Email me when stock reaches', 'woocommerce'), help: lowStockAmountValidationError ||
                            (0, element_1.createInterpolateElement)((0, i18n_1.__)('Make sure to enable notifications in <link>store settings.</link>', 'woocommerce'), {
                                link: ((0, element_1.createElement)(components_1.Link, { href: `${(0, settings_1.getSetting)('adminUrl')}admin.php?page=wc-settings&tab=products&section=inventory`, target: "_blank", type: "external" })),
                            }), className: lowStockAmountValidationError && 'has-error' },
                        (0, element_1.createElement)(components_2.__experimentalInputControl, { id: id, ref: lowStockAmountRef, name: 'low_stock_amount', placeholder: (0, i18n_1.sprintf)(
                            // translators: Default quantity to notify merchants of low stock.
                            (0, i18n_1.__)('%d (store default)', 'woocommerce'), notifyLowStockAmount), onChange: setLowStockAmount, onBlur: validateLowStockAmount, value: lowStockAmount, type: "number", min: 0 }))),
                (0, element_1.createElement)("div", { className: "wp-block-column" })))));
}
exports.Edit = Edit;
