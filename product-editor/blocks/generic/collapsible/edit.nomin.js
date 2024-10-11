"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const block_templates_1 = require("@woocommerce/block-templates");
const components_1 = require("@woocommerce/components");
const element_1 = require("@wordpress/element");
const block_editor_1 = require("@wordpress/block-editor");
function Edit({ attributes, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { toggleText, initialCollapsed, persistRender = true } = attributes;
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(components_1.CollapsibleContent, { toggleText: toggleText, initialCollapsed: initialCollapsed, persistRender: persistRender },
            (0, element_1.createElement)(block_editor_1.InnerBlocks, { templateLock: "all" }))));
}
exports.Edit = Edit;
