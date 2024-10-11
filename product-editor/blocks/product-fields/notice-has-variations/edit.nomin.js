"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const components_1 = require("@wordpress/components");
const block_templates_1 = require("@woocommerce/block-templates");
const navigation_1 = require("@woocommerce/navigation");
const core_data_1 = require("@wordpress/core-data");
/**
 * Internal dependencies
 */
const notice_1 = require("../../../components/notice");
const utils_1 = require("../../../utils");
function Edit({ attributes, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { buttonText, content, title, type = 'info' } = attributes;
    const [productAttributes] = (0, core_data_1.useEntityProp)('postType', 'product', 'attributes');
    const [productType] = (0, core_data_1.useEntityProp)('postType', 'product', 'type');
    const isOptionsNoticeVisible = (0, utils_1.hasAttributesUsedForVariations)(productAttributes) &&
        productType === 'variable';
    return ((0, element_1.createElement)("div", { ...blockProps }, isOptionsNoticeVisible && ((0, element_1.createElement)(notice_1.Notice, { content: content, title: title, type: type },
        (0, element_1.createElement)(components_1.Button, { isSecondary: true, onClick: () => (0, navigation_1.navigateTo)({
                url: (0, navigation_1.getNewPath)({ tab: 'variations' }),
            }) }, buttonText)))));
}
exports.Edit = Edit;
