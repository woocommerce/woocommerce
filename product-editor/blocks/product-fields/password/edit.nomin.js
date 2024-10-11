"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const core_data_1 = require("@wordpress/core-data");
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const require_password_1 = require("../../../components/require-password");
function Edit({ attributes, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { label } = attributes;
    const [postPassword, setPostPassword] = (0, core_data_1.useEntityProp)('postType', 'product', 'post_password');
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(require_password_1.RequirePassword, { label: label, postPassword: postPassword, onInputChange: setPostPassword })));
}
exports.Edit = Edit;
