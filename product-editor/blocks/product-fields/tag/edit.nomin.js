"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const block_templates_1 = require("@woocommerce/block-templates");
const element_1 = require("@wordpress/element");
const components_1 = require("@wordpress/components");
const compose_1 = require("@wordpress/compose");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const tags_field_1 = require("../../../components/tags-field");
function Edit({ attributes, context: { postType, isInSelectedTab }, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { name, label, placeholder } = attributes;
    const [tags, setTags] = (0, core_data_1.useEntityProp)('postType', postType || 'product', name || 'tags');
    const tagFieldId = (0, compose_1.useInstanceId)(components_1.BaseControl, 'tag-field');
    return ((0, element_1.createElement)("div", { ...blockProps }, (0, element_1.createElement)(tags_field_1.TagField, { id: tagFieldId, isVisible: isInSelectedTab, label: label || (0, i18n_1.__)('Tags', 'woocommerce'), placeholder: placeholder ||
            (0, i18n_1.__)('Search or create tags…', 'woocommerce'), onChange: setTags, value: tags || [] })));
}
exports.Edit = Edit;
