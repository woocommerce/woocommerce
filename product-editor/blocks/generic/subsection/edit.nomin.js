"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.SubsectionBlockEdit = void 0;
/**
 * External dependencies
 */
const classnames_1 = __importDefault(require("classnames"));
const element_1 = require("@wordpress/element");
const block_templates_1 = require("@woocommerce/block-templates");
const block_editor_1 = require("@wordpress/block-editor");
const section_header_1 = require("../../../components/section-header");
function SubsectionBlockEdit({ attributes, }) {
    const { description, title, blockGap } = attributes;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const innerBlockProps = (0, block_editor_1.useInnerBlocksProps)({
        className: (0, classnames_1.default)('wp-block-woocommerce-product-section-header__content', `wp-block-woocommerce-product-section-header__content--block-gap-${blockGap}`),
    }, { templateLock: 'all' });
    const SubsectionTagName = title ? 'fieldset' : 'div';
    return ((0, element_1.createElement)(SubsectionTagName, { ...blockProps },
        title && ((0, element_1.createElement)(section_header_1.SectionHeader, { description: description, sectionTagName: SubsectionTagName, title: title })),
        (0, element_1.createElement)("div", { ...innerBlockProps })));
}
exports.SubsectionBlockEdit = SubsectionBlockEdit;
