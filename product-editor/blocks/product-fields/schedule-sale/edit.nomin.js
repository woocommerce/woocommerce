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
const components_1 = require("@woocommerce/components");
const tracks_1 = require("@woocommerce/tracks");
const components_2 = require("@wordpress/components");
const core_data_1 = require("@wordpress/core-data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const moment_1 = __importDefault(require("moment"));
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore We need this to get the datetime format for the DateTimePickerControl.
// eslint-disable-next-line @woocommerce/dependency-group
const date_1 = require("@wordpress/date");
const use_product_edits_1 = require("../../../hooks/use-product-edits");
const validation_context_1 = require("../../../contexts/validation-context");
function Edit({ attributes, clientId, context, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { hasEdit } = (0, use_product_edits_1.useProductEdits)();
    const dateTimeFormat = (0, date_1.getSettings)().formats.datetime;
    const [showScheduleSale, setShowScheduleSale] = (0, element_1.useState)(false);
    const [salePrice] = (0, core_data_1.useEntityProp)('postType', context.postType || 'product', 'sale_price');
    const isSalePriceGreaterThanZero = Number.parseFloat(salePrice || '0') > 0;
    const [dateOnSaleFromGmt, setDateOnSaleFromGmt] = (0, core_data_1.useEntityProp)('postType', context.postType || 'product', 'date_on_sale_from_gmt');
    const [dateOnSaleToGmt, setDateOnSaleToGmt] = (0, core_data_1.useEntityProp)('postType', context.postType || 'product', 'date_on_sale_to_gmt');
    const today = (0, moment_1.default)().startOf('minute').toISOString();
    function handleToggleChange(value) {
        (0, tracks_1.recordEvent)('product_pricing_schedule_sale_toggle_click', {
            enabled: value,
        });
        setShowScheduleSale(value);
        if (value) {
            setDateOnSaleFromGmt(today);
            setDateOnSaleToGmt('');
        }
        else {
            setDateOnSaleFromGmt('');
            setDateOnSaleToGmt('');
        }
    }
    // Hide and clean date fields if the user manually changes
    // the sale price to zero or less.
    (0, element_1.useEffect)(() => {
        if (hasEdit('sale_price') && !isSalePriceGreaterThanZero) {
            setShowScheduleSale(false);
            setDateOnSaleFromGmt('');
            setDateOnSaleToGmt('');
        }
    }, [isSalePriceGreaterThanZero]);
    // Automatically show date fields if `from` or `to` dates have
    // any value.
    (0, element_1.useEffect)(() => {
        if (dateOnSaleFromGmt || dateOnSaleToGmt) {
            setShowScheduleSale(true);
        }
    }, [dateOnSaleFromGmt, dateOnSaleToGmt]);
    const _dateOnSaleFrom = (0, moment_1.default)(dateOnSaleFromGmt, moment_1.default.ISO_8601, true);
    const _dateOnSaleTo = (0, moment_1.default)(dateOnSaleToGmt, moment_1.default.ISO_8601, true);
    const { ref: dateOnSaleFromGmtRef, error: dateOnSaleFromGmtValidationError, validate: validateDateOnSaleFromGmt, } = (0, validation_context_1.useValidation)(`date_on_sale_from_gmt-${clientId}`, async function dateOnSaleFromValidator() {
        if (showScheduleSale && dateOnSaleFromGmt) {
            if (!_dateOnSaleFrom.isValid()) {
                return {
                    message: (0, i18n_1.__)('Please enter a valid date.', 'woocommerce'),
                };
            }
            if (_dateOnSaleFrom.isAfter(_dateOnSaleTo)) {
                return {
                    message: (0, i18n_1.__)('The start date of the sale must be before the end date.', 'woocommerce'),
                };
            }
        }
    }, [showScheduleSale, dateOnSaleFromGmt, _dateOnSaleFrom, _dateOnSaleTo]);
    const { ref: dateOnSaleToGmtRef, error: dateOnSaleToGmtValidationError, validate: validateDateOnSaleToGmt, } = (0, validation_context_1.useValidation)(`date_on_sale_to_gmt-${clientId}`, async function dateOnSaleToValidator() {
        if (showScheduleSale && dateOnSaleToGmt) {
            if (!_dateOnSaleTo.isValid()) {
                return {
                    message: (0, i18n_1.__)('Please enter a valid date.', 'woocommerce'),
                };
            }
            if (_dateOnSaleTo.isBefore(_dateOnSaleFrom)) {
                return {
                    message: (0, i18n_1.__)('The end date of the sale must be after the start date.', 'woocommerce'),
                };
            }
        }
    }, [showScheduleSale, dateOnSaleFromGmt, _dateOnSaleFrom, _dateOnSaleTo]);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_2.ToggleControl, { label: (0, i18n_1.__)('Schedule sale', 'woocommerce'), checked: showScheduleSale, onChange: handleToggleChange, 
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore disabled prop exists
            disabled: !isSalePriceGreaterThanZero }),
        showScheduleSale && ((0, element_1.createElement)("div", { className: "wp-block-columns wp-block-woocommerce-product-schedule-sale-fields__content" },
            (0, element_1.createElement)("div", { className: "wp-block-column" },
                (0, element_1.createElement)(components_1.DateTimePickerControl, { ref: dateOnSaleFromGmtRef, label: (0, i18n_1.__)('From', 'woocommerce'), placeholder: (0, i18n_1.__)('Sale start date and time (optional)', 'woocommerce'), dateTimeFormat: dateTimeFormat, currentDate: dateOnSaleFromGmt, onChange: setDateOnSaleFromGmt, className: dateOnSaleFromGmtValidationError && 'has-error', help: dateOnSaleFromGmtValidationError, onBlur: () => validateDateOnSaleFromGmt() })),
            (0, element_1.createElement)("div", { className: "wp-block-column" },
                (0, element_1.createElement)(components_1.DateTimePickerControl, { ref: dateOnSaleToGmtRef, label: (0, i18n_1.__)('To', 'woocommerce'), placeholder: (0, i18n_1.__)('Sale end date and time (optional)', 'woocommerce'), dateTimeFormat: dateTimeFormat, currentDate: dateOnSaleToGmt, onChange: (value) => setDateOnSaleToGmt((0, moment_1.default)(value)
                        .startOf('minute')
                        .toISOString()), onBlur: () => validateDateOnSaleToGmt(), className: dateOnSaleToGmtValidationError && 'has-error', help: dateOnSaleToGmtValidationError }))))));
}
exports.Edit = Edit;
