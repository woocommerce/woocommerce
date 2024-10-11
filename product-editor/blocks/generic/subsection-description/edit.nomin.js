"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.SubsectionDescriptionBlockEdit = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
/**
 * Internal dependencies
 */
const block_slot_fill_1 = require("../../../components/block-slot-fill");
function SubsectionDescriptionBlockEdit({ attributes, }) {
    const { content } = attributes;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    return ((0, element_1.createElement)(block_slot_fill_1.BlockFill, { ...blockProps, name: "section-description", slotContainerBlockName: "woocommerce/product-subsection" },
        (0, element_1.createElement)("div", null, content)));
}
exports.SubsectionDescriptionBlockEdit = SubsectionDescriptionBlockEdit;
