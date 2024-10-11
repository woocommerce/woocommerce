"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const notice_1 = require("../../../components/notice");
const sanitize_html_1 = require("../../../utils/sanitize-html");
function Edit({ attributes, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(notice_1.Notice, { content: (0, element_1.createElement)("div", { dangerouslySetInnerHTML: (0, sanitize_html_1.sanitizeHTML)(attributes.message) }) })));
}
exports.Edit = Edit;
