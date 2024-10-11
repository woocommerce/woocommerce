"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
const data_1 = require("@wordpress/data");
const deprecated_1 = __importDefault(require("@wordpress/deprecated"));
const element_1 = require("@wordpress/element");
const block_editor_1 = require("@wordpress/block-editor");
const block_templates_1 = require("@woocommerce/block-templates");
const components_1 = require("@woocommerce/components");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
function Edit({ attributes, context, }) {
    (0, deprecated_1.default)('`woocommerce/conditional` block', {
        alternative: '`hideConditions` attribute on any block',
    });
    const { postType } = context;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { mustMatch } = attributes;
    const productId = (0, core_data_1.useEntityId)('postType', postType);
    const displayBlocks = (0, data_1.useSelect)((select) => {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        const product = select('core').getEditedEntityRecord('postType', postType, productId);
        for (const [prop, values] of Object.entries(mustMatch)) {
            if (!values.includes(product[prop])) {
                return false;
            }
        }
        return true;
    }, [postType, productId, mustMatch]);
    return ((0, element_1.createElement)(components_1.DisplayState, { ...blockProps, state: displayBlocks ? 'visible' : 'visually-hidden' },
        (0, element_1.createElement)(block_editor_1.InnerBlocks, { templateLock: "all" })));
}
exports.Edit = Edit;
