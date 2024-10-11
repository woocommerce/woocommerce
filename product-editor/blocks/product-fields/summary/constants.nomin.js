"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ALIGNMENT_CONTROLS = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
exports.ALIGNMENT_CONTROLS = [
    {
        icon: icons_1.alignLeft,
        title: (0, i18n_1.__)('Align text left', 'woocommerce'),
        align: 'left',
    },
    {
        icon: icons_1.alignCenter,
        title: (0, i18n_1.__)('Align text center', 'woocommerce'),
        align: 'center',
    },
    {
        icon: icons_1.alignRight,
        title: (0, i18n_1.__)('Align text right', 'woocommerce'),
        align: 'right',
    },
    {
        icon: icons_1.alignJustify,
        title: (0, i18n_1.__)('Align text justify', 'woocommerce'),
        align: 'justify',
    },
];
