"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const core_data_1 = require("@wordpress/core-data");
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const catalog_visibility_1 = require("../../../components/catalog-visibility");
function Edit({ attributes, }) {
    const { label, visibility } = attributes;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [catalogVisibility, setCatalogVisibility] = (0, core_data_1.useEntityProp)('postType', 'product', 'catalog_visibility');
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(catalog_visibility_1.CatalogVisibility, { catalogVisibility: catalogVisibility, label: label, visibility: visibility, onCheckboxChange: setCatalogVisibility })));
}
exports.Edit = Edit;
