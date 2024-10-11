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
const navigation_1 = require("@woocommerce/navigation");
const tracks_1 = require("@woocommerce/tracks");
const compose_1 = require("@wordpress/compose");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const components_2 = require("@wordpress/components");
/**
 * Internal dependencies
 */
const use_currency_input_props_1 = require("../../../hooks/use-currency-input-props");
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const label_1 = require("../../../components/label/label");
function Edit({ attributes, context: { postType }, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { property, label = (0, i18n_1.__)('Price', 'woocommerce'), help, disabled, tooltip, } = attributes;
    const [price, setPrice] = (0, use_product_entity_prop_1.default)(property, {
        postType,
        fallbackValue: '',
    });
    const inputProps = (0, use_currency_input_props_1.useCurrencyInputProps)({
        value: price || '',
        onChange: setPrice,
    });
    const interpolatedHelp = help
        ? (0, element_1.createInterpolateElement)(help, {
            PricingTab: ((0, element_1.createElement)(components_1.Link, { href: (0, navigation_1.getNewPath)({ tab: 'pricing' }), onClick: () => {
                    (0, tracks_1.recordEvent)('product_pricing_help_click');
                } })),
        })
        : null;
    const priceId = (0, compose_1.useInstanceId)(components_2.BaseControl, 'wp-block-woocommerce-product-pricing-field');
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_2.BaseControl, { id: priceId, help: interpolatedHelp },
            (0, element_1.createElement)(components_2.__experimentalInputControl, { ...inputProps, disabled: disabled, id: priceId, name: property, label: tooltip ? ((0, element_1.createElement)(label_1.Label, { label: label, tooltip: tooltip })) : (label) }))));
}
exports.Edit = Edit;
