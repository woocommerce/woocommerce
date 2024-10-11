"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.RTLToolbarButton = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const components_1 = require("@wordpress/components");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
function RTLToolbarButton({ direction, onChange, }) {
    if (!(0, i18n_1.isRTL)()) {
        return null;
    }
    return ((0, element_1.createElement)(components_1.ToolbarButton, { icon: icons_1.formatLtr, title: (0, i18n_1._x)('Left to right', 'editor button', 'woocommerce'), isActive: direction === 'ltr', onClick: () => onChange === null || onChange === void 0 ? void 0 : onChange(direction === 'ltr' ? undefined : 'ltr') }));
}
exports.RTLToolbarButton = RTLToolbarButton;
