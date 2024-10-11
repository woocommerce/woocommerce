"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const block_templates_1 = require("@woocommerce/block-templates");
const compose_1 = require("@wordpress/compose");
const core_data_1 = require("@wordpress/core-data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const components_1 = require("@wordpress/components");
const validation_context_1 = require("../../../contexts/validation-context");
function Edit({ attributes, clientId, context, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [manageStock] = (0, core_data_1.useEntityProp)('postType', context.postType, 'manage_stock');
    const [stockQuantity, setStockQuantity] = (0, core_data_1.useEntityProp)('postType', context.postType, 'stock_quantity');
    const stockQuantityId = (0, compose_1.useInstanceId)(components_1.BaseControl, 'product_stock_quantity');
    const { ref: stockQuantityRef, error: stockQuantityValidationError, validate: validateStockQuantity, } = (0, validation_context_1.useValidation)(`stock_quantity-${clientId}`, async function stockQuantityValidator() {
        if (manageStock && stockQuantity && stockQuantity < 0) {
            return {
                message: (0, i18n_1.__)('Stock quantity must be a positive number.', 'woocommerce'),
            };
        }
    }, [manageStock, stockQuantity]);
    (0, element_1.useEffect)(() => {
        if (manageStock && stockQuantity === null) {
            setStockQuantity(1);
        }
    }, [manageStock, stockQuantity]);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)("div", { className: "wp-block-columns" },
            (0, element_1.createElement)("div", { className: "wp-block-column" },
                (0, element_1.createElement)(components_1.BaseControl, { id: stockQuantityId, className: stockQuantityValidationError && 'has-error', help: stockQuantityValidationError !== null && stockQuantityValidationError !== void 0 ? stockQuantityValidationError : '' },
                    (0, element_1.createElement)(components_1.__experimentalInputControl, { id: stockQuantityId, name: "stock_quantity", ref: stockQuantityRef, label: (0, i18n_1.__)('Available stock', 'woocommerce'), value: stockQuantity, onChange: setStockQuantity, onBlur: validateStockQuantity, type: "number", min: 0 }))),
            (0, element_1.createElement)("div", { className: "wp-block-column" }))));
}
exports.Edit = Edit;
