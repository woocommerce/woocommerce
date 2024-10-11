"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.TabButton = exports.DEFAULT_TAB_ORDER = void 0;
/**
 * External dependencies
 */
const components_1 = require("@wordpress/components");
const classnames_1 = __importDefault(require("classnames"));
const element_1 = require("@wordpress/element");
/**
 * Internal dependencies
 */
const constants_1 = require("../../../components/tabs/constants");
exports.DEFAULT_TAB_ORDER = 100;
const OrderedWrapper = ({ children, }) => (0, element_1.createElement)(element_1.Fragment, null, children);
function TabButton({ children, className, id, order = exports.DEFAULT_TAB_ORDER, selected = false, }) {
    const classes = (0, classnames_1.default)('wp-block-woocommerce-product-tab__button', className, { 'is-selected': selected });
    return ((0, element_1.createElement)(components_1.Fill, { name: constants_1.TABS_SLOT_NAME }, (fillProps) => {
        const { onClick } = fillProps;
        return ((0, element_1.createElement)(OrderedWrapper, { order: order },
            (0, element_1.createElement)(components_1.Button, { key: id, className: classes, onClick: () => onClick(id), id: `woocommerce-product-tab__${id}`, "aria-controls": `woocommerce-product-tab__${id}-content`, "aria-selected": selected, tabIndex: selected ? undefined : -1, role: "tab" }, children)));
    }));
}
exports.TabButton = TabButton;
