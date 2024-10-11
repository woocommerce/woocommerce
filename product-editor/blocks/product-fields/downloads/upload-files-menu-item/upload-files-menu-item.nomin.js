"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.UploadFilesMenuItem = void 0;
const components_1 = require("@wordpress/components");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
const media_utils_1 = require("@wordpress/media-utils");
function UploadFilesMenuItem({ allowedTypes, maxUploadFileSize, onUploadSuccess, onUploadError, }) {
    var _a;
    const resolvedMaxUploadFileSize = maxUploadFileSize ||
        ((_a = window.productBlockEditorSettings) === null || _a === void 0 ? void 0 : _a.maxUploadFileSize) ||
        10 * 1024 * 1024; // 10 MB by default if not set and not provided by the settings
    function handleFormFileUploadChange(event) {
        const filesList = event.currentTarget.files;
        (0, media_utils_1.uploadMedia)({
            allowedTypes,
            filesList,
            maxUploadFileSize: resolvedMaxUploadFileSize,
            onFileChange: onUploadSuccess,
            onError: onUploadError,
            additionalData: {
                type: 'downloadable_product',
            },
        });
    }
    return ((0, element_1.createElement)(components_1.FormFileUpload, { multiple: true, onChange: handleFormFileUploadChange, render: ({ openFileDialog }) => ((0, element_1.createElement)(components_1.MenuItem, { icon: icons_1.upload, iconPosition: "left", onClick: openFileDialog, info: (0, i18n_1.__)('Select files from your device', 'woocommerce') }, (0, i18n_1.__)('Upload', 'woocommerce'))) }));
}
exports.UploadFilesMenuItem = UploadFilesMenuItem;
