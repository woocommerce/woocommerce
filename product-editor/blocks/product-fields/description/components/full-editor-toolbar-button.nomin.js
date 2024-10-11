"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
const components_1 = require("@wordpress/components");
const i18n_1 = require("@wordpress/i18n");
const tracks_1 = require("@woocommerce/tracks");
const data_1 = require("@wordpress/data");
const core_data_1 = require("@wordpress/core-data");
const blocks_1 = require("@wordpress/blocks");
/**
 * Internal dependencies
 */
const product_editor_ui_1 = require("../../../../store/product-editor-ui");
const edit_1 = require("../edit");
const get_gutenberg_version_1 = require("../../../../utils/get-gutenberg-version");
// There is a bug in Gutenberg 17.9 that causes a crash in the full editor.
// This should be fixed in Gutenberg 18.0 (see https://github.com/WordPress/gutenberg/pull/59800).
// Once we only support Gutenberg 18.0 and above, we can remove this check.
function isGutenbergVersionWithCrashInFullEditor() {
    const gutenbergVersion = (0, get_gutenberg_version_1.getGutenbergVersion)();
    return gutenbergVersion >= 17.9 && gutenbergVersion < 18.0;
}
function shouldForceFullEditor() {
    var _a;
    return (((_a = localStorage
        .getItem('__unsupported_force_product_editor_description_full_editor')) === null || _a === void 0 ? void 0 : _a.trim().toLowerCase()) === 'true');
}
function FullEditorToolbarButton({ label = (0, i18n_1.__)('Edit Product description', 'woocommerce'), text = (0, i18n_1.__)('Full editor', 'woocommerce'), }) {
    const { openModalEditor, setModalEditorBlocks } = (0, data_1.dispatch)(product_editor_ui_1.store);
    const [description] = (0, core_data_1.useEntityProp)('postType', 'product', 'description');
    return ((0, element_1.createElement)(components_1.ToolbarButton, { label: label, onClick: () => {
            if (isGutenbergVersionWithCrashInFullEditor()) {
                if (shouldForceFullEditor()) {
                    // eslint-disable-next-line no-alert
                    alert((0, i18n_1.__)('The version of the Gutenberg plugin installed causes a crash in the full editor. You are proceeding at your own risk and may experience crashes.', 'woocommerce'));
                }
                else {
                    // eslint-disable-next-line no-alert
                    alert((0, i18n_1.__)('The version of the Gutenberg plugin installed causes a crash in the full editor. To prevent this, the full editor has been disabled.', 'woocommerce'));
                    return;
                }
            }
            let parsedBlocks = (0, blocks_1.parse)(description);
            const freeformContent = (0, edit_1.getContentFromFreeform)(parsedBlocks);
            // replace the freeform block with a paragraph block
            if (freeformContent) {
                parsedBlocks = (0, blocks_1.rawHandler)({ HTML: freeformContent });
            }
            setModalEditorBlocks(parsedBlocks);
            (0, tracks_1.recordEvent)('product_add_description_click');
            openModalEditor();
        } }, text));
}
exports.default = FullEditorToolbarButton;
