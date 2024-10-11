"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.DescriptionBlockEdit = exports.getContentFromFreeform = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const blocks_1 = require("@wordpress/blocks");
const data_1 = require("@wordpress/data");
const classnames_1 = __importDefault(require("classnames"));
const block_templates_1 = require("@woocommerce/block-templates");
const core_data_1 = require("@wordpress/core-data");
const i18n_1 = require("@wordpress/i18n");
const block_editor_1 = require("@wordpress/block-editor");
/**
 * Internal dependencies
 */
const modal_editor_welcome_guide_1 = __importDefault(require("../../../components/modal-editor-welcome-guide"));
const product_editor_ui_1 = require("../../../store/product-editor-ui");
const full_editor_toolbar_button_1 = __importDefault(require("./components/full-editor-toolbar-button"));
/**
 * Check whether the parsed blocks become from the summary block.
 *
 * @param {BlockInstance[]} blocks - The block list
 * @return {string|false} The content of the freeform block if it's a freeform block, false otherwise.
 */
function getContentFromFreeform(blocks) {
    // Check whether the parsed blocks become from the summary block:
    const isCoreFreeformBlock = blocks.length === 1 && blocks[0].name === 'core/freeform';
    if (isCoreFreeformBlock) {
        return blocks[0].attributes.content;
    }
    return false;
}
exports.getContentFromFreeform = getContentFromFreeform;
function DescriptionBlockEdit({ attributes, }) {
    const [description, setDescription] = (0, core_data_1.useEntityProp)('postType', 'product', 'description');
    const [descriptionBlocks, setDescriptionBlocks] = (0, element_1.useState)([]);
    // Pick Modal editor data from the store.
    const { isModalEditorOpen, modalEditorBlocks, hasChanged } = (0, data_1.useSelect)((select) => {
        return {
            isModalEditorOpen: select(product_editor_ui_1.store).isModalEditorOpen(),
            modalEditorBlocks: select(product_editor_ui_1.store).getModalEditorBlocks(),
            hasChanged: select(product_editor_ui_1.store).getModalEditorContentHasChanged(),
        };
    }, []);
    // Parse the description into blocks.
    (0, element_1.useEffect)(() => {
        if (!description) {
            setDescriptionBlocks([]);
            return;
        }
        /*
         * First quick check to avoid parsing process,
         * since it's an expensive operation.
         */
        if (description.indexOf('<!-- wp:') === -1) {
            return;
        }
        const parsedBlocks = (0, blocks_1.parse)(description);
        // Check whether the parsed blocks become from the summary block:
        if (getContentFromFreeform(parsedBlocks)) {
            return;
        }
        setDescriptionBlocks(parsedBlocks);
    }, [description]);
    /*
     * From Modal Editor -> Description entity.
     * Update the description when the modal editor blocks change.
     */
    (0, element_1.useEffect)(() => {
        if (!hasChanged) {
            return;
        }
        const html = (0, blocks_1.serialize)(modalEditorBlocks);
        setDescription(html);
    }, [modalEditorBlocks, setDescription, hasChanged]);
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes, {
        className: (0, classnames_1.default)({ 'has-blocks': !!description.length }),
        tabIndex: 0,
    });
    const innerBlockProps = (0, block_editor_1.useInnerBlocksProps)({}, {
        templateLock: 'contentOnly',
        allowedBlocks: ['woocommerce/product-summary-field'],
    });
    return ((0, element_1.createElement)("div", { ...blockProps },
        !!(descriptionBlocks === null || descriptionBlocks === void 0 ? void 0 : descriptionBlocks.length) ? ((0, element_1.createElement)(element_1.Fragment, null,
            (0, element_1.createElement)(block_editor_1.BlockControls, null,
                (0, element_1.createElement)(full_editor_toolbar_button_1.default, { text: (0, i18n_1.__)('Edit in full editor', 'woocommerce') })),
            (0, element_1.createElement)(block_editor_1.BlockPreview, { blocks: descriptionBlocks, viewportWidth: 800, additionalStyles: [
                    { css: 'body { padding: 32px; height: 10000px }' }, // hack: setting height to 10000px to ensure the preview is not cut off.
                ] }))) : ((0, element_1.createElement)("div", { ...innerBlockProps })),
        isModalEditorOpen && (0, element_1.createElement)(modal_editor_welcome_guide_1.default, null)));
}
exports.DescriptionBlockEdit = DescriptionBlockEdit;
