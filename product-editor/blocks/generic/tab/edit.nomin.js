"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.TabBlockEdit = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const block_editor_1 = require("@wordpress/block-editor");
const classnames_1 = __importDefault(require("classnames"));
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const components_1 = require("@woocommerce/components");
/**
 * Internal dependencies
 */
const tab_button_1 = require("./tab-button");
function TabBlockEdit({ setAttributes, attributes, context, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { id, title, _templateBlockOrder: order, isSelected } = attributes;
    const classes = (0, classnames_1.default)('wp-block-woocommerce-product-tab__content', {
        'is-selected': isSelected,
    });
    const [canRenderChildren, setCanRenderChildren] = (0, element_1.useState)(false);
    (0, element_1.useEffect)(() => {
        if (!context.selectedTab)
            return;
        const isSelectedInContext = context.selectedTab === id;
        setAttributes({ isSelected: isSelectedInContext });
        if (isSelectedInContext) {
            setCanRenderChildren(true);
            return;
        }
        const timeoutId = setTimeout(setCanRenderChildren, 300, true);
        return () => clearTimeout(timeoutId);
    }, [context.selectedTab, id, setAttributes]);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(tab_button_1.TabButton, { id: id, selected: isSelected, order: order }, title),
        (0, element_1.createElement)("div", { id: `woocommerce-product-tab__${id}-content`, "aria-labelledby": `woocommerce-product-tab__${id}`, role: "tabpanel", className: classes },
            (0, element_1.createElement)(components_1.__experimentalErrorBoundary, { errorMessage: (0, i18n_1.__)('An unexpected error occurred in this tab. Make sure any unsaved changes are saved and then try reloading the page to see if the error recurs.', 'woocommerce'), onError: (error, errorInfo) => {
                    // eslint-disable-next-line no-console
                    console.error(`Error caught in tab '${id}'`, error, errorInfo);
                } }, canRenderChildren && (
            /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */
            /* @ts-ignore Content only template locking does exist for this property. */
            (0, element_1.createElement)(block_editor_1.InnerBlocks, { templateLock: "contentOnly" }))))));
}
exports.TabBlockEdit = TabBlockEdit;
