"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const block_templates_1 = require("@woocommerce/block-templates");
const data_1 = require("@woocommerce/data");
const core_data_1 = require("@wordpress/core-data");
const data_2 = require("@wordpress/data");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const shipping_dimensions_image_1 = require("../../../components/shipping-dimensions-image");
const validation_context_1 = require("../../../contexts/validation-context");
const number_control_1 = require("../../../components/number-control");
const SHIPPING_AND_WEIGHT_MIN_VALUE = 0;
const SHIPPING_AND_WEIGHT_MAX_VALUE = 100000000000000;
function Edit({ attributes, clientId, context, }) {
    var _a, _b, _c;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [dimensions, setDimensions] = (0, core_data_1.useEntityProp)('postType', context.postType, 'dimensions');
    const [weight, setWeight] = (0, core_data_1.useEntityProp)('postType', context.postType, 'weight');
    const [virtual] = (0, core_data_1.useEntityProp)('postType', context.postType, 'virtual');
    const [highlightSide, setHighlightSide] = (0, element_1.useState)();
    const { dimensionUnit, weightUnit } = (0, data_2.useSelect)((select) => {
        const { getOption } = select(data_1.OPTIONS_STORE_NAME);
        return {
            dimensionUnit: getOption('woocommerce_dimension_unit'),
            weightUnit: getOption('woocommerce_weight_unit'),
        };
    }, []);
    function getDimensionsControlProps(name, side) {
        var _a;
        return {
            name: `dimensions.${name}`,
            value: (_a = (dimensions && dimensions[name])) !== null && _a !== void 0 ? _a : '',
            onChange: (value) => setDimensions({
                ...(dimensions !== null && dimensions !== void 0 ? dimensions : {}),
                [name]: value,
            }),
            onFocus: () => setHighlightSide(side),
            onBlur: () => setHighlightSide(undefined),
            suffix: dimensionUnit,
            disabled: attributes.disabled || virtual,
            min: SHIPPING_AND_WEIGHT_MIN_VALUE,
            max: SHIPPING_AND_WEIGHT_MAX_VALUE,
        };
    }
    const widthFieldId = `dimensions_width-${clientId}`;
    const { ref: dimensionsWidthRef, error: dimensionsWidthValidationError, validate: validateDimensionsWidth, } = (0, validation_context_1.useValidation)(widthFieldId, async function dimensionsWidthValidator() {
        if ((dimensions === null || dimensions === void 0 ? void 0 : dimensions.width) && +dimensions.width <= 0) {
            return {
                message: (0, i18n_1.__)('Width must be greater than zero.', 'woocommerce'),
            };
        }
    }, [dimensions === null || dimensions === void 0 ? void 0 : dimensions.width]);
    const lengthFieldId = `dimensions_length-${clientId}`;
    const { ref: dimensionsLengthRef, error: dimensionsLengthValidationError, validate: validateDimensionsLength, } = (0, validation_context_1.useValidation)(lengthFieldId, async function dimensionsLengthValidator() {
        if ((dimensions === null || dimensions === void 0 ? void 0 : dimensions.length) && +dimensions.length <= 0) {
            return {
                message: (0, i18n_1.__)('Length must be greater than zero.', 'woocommerce'),
            };
        }
    }, [dimensions === null || dimensions === void 0 ? void 0 : dimensions.length]);
    const heightFieldId = `dimensions_height-${clientId}`;
    const { ref: dimensionsHeightRef, error: dimensionsHeightValidationError, validate: validateDimensionsHeight, } = (0, validation_context_1.useValidation)(heightFieldId, async function dimensionsHeightValidator() {
        if ((dimensions === null || dimensions === void 0 ? void 0 : dimensions.height) && +dimensions.height <= 0) {
            return {
                message: (0, i18n_1.__)('Height must be greater than zero.', 'woocommerce'),
            };
        }
    }, [dimensions === null || dimensions === void 0 ? void 0 : dimensions.height]);
    const weightFieldId = `weight-${clientId}`;
    const { ref: weightRef, error: weightValidationError, validate: validateWeight, } = (0, validation_context_1.useValidation)(weightFieldId, async function weightValidator() {
        if (weight && +weight <= 0) {
            return {
                message: (0, i18n_1.__)('Weight must be greater than zero.', 'woocommerce'),
            };
        }
    }, [weight]);
    const dimensionsWidthProps = {
        ...getDimensionsControlProps('width', 'A'),
        ref: dimensionsWidthRef,
        onBlur: validateDimensionsWidth,
        id: widthFieldId,
    };
    const dimensionsLengthProps = {
        ...getDimensionsControlProps('length', 'B'),
        ref: dimensionsLengthRef,
        onBlur: validateDimensionsLength,
        id: lengthFieldId,
    };
    const dimensionsHeightProps = {
        ...getDimensionsControlProps('height', 'C'),
        ref: dimensionsHeightRef,
        onBlur: validateDimensionsHeight,
        id: heightFieldId,
    };
    const weightProps = {
        id: weightFieldId,
        name: 'weight',
        value: weight !== null && weight !== void 0 ? weight : '',
        onChange: setWeight,
        suffix: weightUnit,
        ref: weightRef,
        onBlur: validateWeight,
        disabled: attributes.disabled || virtual,
        min: SHIPPING_AND_WEIGHT_MIN_VALUE,
        max: SHIPPING_AND_WEIGHT_MAX_VALUE,
    };
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)("h4", null, (0, i18n_1.__)('Dimensions', 'woocommerce')),
        (0, element_1.createElement)("div", { className: "wp-block-columns" },
            (0, element_1.createElement)("div", { className: "wp-block-column" },
                (0, element_1.createElement)(number_control_1.NumberControl, { label: (0, element_1.createInterpolateElement)((0, i18n_1.__)('Width <Side />', 'woocommerce'), { Side: (0, element_1.createElement)("span", null, "A") }), error: dimensionsWidthValidationError, ...dimensionsWidthProps }),
                (0, element_1.createElement)(number_control_1.NumberControl, { label: (0, element_1.createInterpolateElement)((0, i18n_1.__)('Length <Side />', 'woocommerce'), { Side: (0, element_1.createElement)("span", null, "B") }), error: dimensionsLengthValidationError, ...dimensionsLengthProps }),
                (0, element_1.createElement)(number_control_1.NumberControl, { label: (0, element_1.createInterpolateElement)((0, i18n_1.__)('Height <Side />', 'woocommerce'), { Side: (0, element_1.createElement)("span", null, "C") }), error: dimensionsHeightValidationError, ...dimensionsHeightProps }),
                (0, element_1.createElement)(number_control_1.NumberControl, { label: (0, i18n_1.__)('Weight', 'woocommerce'), error: weightValidationError, ...weightProps })),
            (0, element_1.createElement)("div", { className: "wp-block-column" },
                (0, element_1.createElement)(shipping_dimensions_image_1.ShippingDimensionsImage, { highlight: highlightSide, className: "wp-block-woocommerce-product-shipping-dimensions-fields__dimensions-image", labels: {
                        A: ((_a = dimensionsWidthProps.value) === null || _a === void 0 ? void 0 : _a.length)
                            ? dimensionsWidthProps.value
                            : undefined,
                        B: ((_b = dimensionsLengthProps.value) === null || _b === void 0 ? void 0 : _b.length)
                            ? dimensionsLengthProps.value
                            : undefined,
                        C: ((_c = dimensionsHeightProps.value) === null || _c === void 0 ? void 0 : _c.length)
                            ? dimensionsHeightProps.value
                            : undefined,
                    } })))));
}
exports.Edit = Edit;
