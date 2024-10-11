"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ParagraphRTLControl = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const components_1 = require("@wordpress/components");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
function ParagraphRTLControl({ direction, onChange, }) {
    function handleClick() {
        if (typeof onChange === 'function') {
            onChange(direction === 'ltr' ? undefined : 'ltr');
        }
    }
    return ((0, element_1.createElement)(element_1.Fragment, null, (0, i18n_1.isRTL)() && ((0, element_1.createElement)(components_1.ToolbarButton, { icon: icons_1.formatLtr, title: (0, i18n_1._x)('Left to right', 'editor button', 'woocommerce'), isActive: direction === 'ltr', onClick: handleClick }))));
}
exports.ParagraphRTLControl = ParagraphRTLControl;
