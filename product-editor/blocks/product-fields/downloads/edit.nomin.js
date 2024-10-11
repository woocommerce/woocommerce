"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.DownloadBlockEdit = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const components_1 = require("@wordpress/components");
const data_1 = require("@wordpress/data");
const element_1 = require("@wordpress/element");
const icons_1 = require("@wordpress/icons");
const block_templates_1 = require("@woocommerce/block-templates");
const components_2 = require("@woocommerce/components");
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
const core_data_1 = require("@wordpress/core-data");
const downloads_menu_1 = require("./downloads-menu");
const manage_download_limits_modal_1 = require("../../../components/manage-download-limits-modal");
const edit_downloads_modal_1 = require("./edit-downloads-modal");
const upload_image_1 = require("./upload-image");
const block_slot_fill_1 = require("../../../components/block-slot-fill");
function getFileName(url) {
    var _a;
    const [name] = (_a = url === null || url === void 0 ? void 0 : url.split('/').reverse()) !== null && _a !== void 0 ? _a : [];
    return name;
}
function stringifyId(id) {
    return id ? String(id) : '';
}
function stringifyEntityId(entity) {
    return { ...entity, id: stringifyId(entity.id) };
}
function DownloadBlockEdit({ attributes, context: { postType }, }) {
    var _a;
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const [downloads, setDownloads] = (0, core_data_1.useEntityProp)('postType', postType, 'downloads');
    const [downloadLimit, setDownloadLimit] = (0, core_data_1.useEntityProp)('postType', postType, 'download_limit');
    const [downloadExpiry, setDownloadExpiry] = (0, core_data_1.useEntityProp)('postType', postType, 'download_expiry');
    const [selectedDownload, setSelectedDownload] = (0, element_1.useState)();
    const { allowedMimeTypes } = (0, data_1.useSelect)((select) => {
        const { getEditorSettings } = select('core/editor');
        return getEditorSettings();
    });
    const allowedTypes = allowedMimeTypes
        ? Object.values(allowedMimeTypes)
        : [];
    const { createErrorNotice } = (0, data_1.useDispatch)('core/notices');
    const [showManageDownloadLimitsModal, setShowManageDownloadLimitsModal] = (0, element_1.useState)(false);
    function handleManageLimitsClick() {
        setShowManageDownloadLimitsModal(true);
    }
    function handleManageDownloadLimitsModalClose() {
        setShowManageDownloadLimitsModal(false);
    }
    function handleManageDownloadLimitsModalSubmit(value) {
        setDownloadLimit(value.downloadLimit);
        setDownloadExpiry(value.downloadExpiry);
        setShowManageDownloadLimitsModal(false);
    }
    function handleFileUpload(files) {
        if (!Array.isArray(files))
            return;
        const newFiles = files.filter((file) => !downloads.some((download) => download.file === file.url));
        if (newFiles.length !== files.length) {
            createErrorNotice(files.length === 1
                ? (0, i18n_1.__)('This file has already been added', 'woocommerce')
                : (0, i18n_1.__)('Some of these files have already been added', 'woocommerce'));
        }
        if (newFiles.length) {
            const uploadedFiles = newFiles.map((file) => ({
                id: stringifyId(file.id),
                file: file.url,
                name: file.title ||
                    file.alt ||
                    file.caption ||
                    getFileName(file.url),
            }));
            const stringifyIds = downloads.map(stringifyEntityId);
            stringifyIds.push(...uploadedFiles);
            setDownloads(stringifyIds);
        }
    }
    function removeDownload(download) {
        const otherDownloads = downloads.reduce(function removeDownloadElement(others, current) {
            if (current.file === download.file) {
                return others;
            }
            return [...others, stringifyEntityId(current)];
        }, []);
        setDownloads(otherDownloads);
    }
    function removeHandler(download) {
        return function handleRemoveClick() {
            removeDownload(download);
        };
    }
    function editHandler(download) {
        return function handleEditClick() {
            setSelectedDownload(stringifyEntityId(download));
        };
    }
    const handleUploadError = function (error) {
        createErrorNotice((0, i18n_1.sprintf)(
        /* translators: %1$s is a line break, %2$s is the detailed error message */
        (0, i18n_1.__)('Error uploading file:%1$s%2$s', 'woocommerce'), '\n', error.message));
    };
    const handleLinkError = function (error) {
        createErrorNotice((0, i18n_1.sprintf)(
        /* translators: %1$s is a line break, %2$s is the detailed error message */
        (0, i18n_1.__)('Error linking file:%1$s%2$s', 'woocommerce'), '\n', error));
    };
    function editDownloadsModalSaveHandler(value) {
        return function handleEditDownloadsModalSave() {
            const newDownloads = downloads
                .map(stringifyEntityId)
                .map((obj) => obj.id === value.id ? value : obj);
            setDownloads(newDownloads);
            setSelectedDownload(null);
        };
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(block_slot_fill_1.SectionActions, null,
            Boolean(downloads.length) && ((0, element_1.createElement)(components_1.Button, { variant: "tertiary", onClick: handleManageLimitsClick }, (0, i18n_1.__)('Manage limits', 'woocommerce'))),
            (0, element_1.createElement)(downloads_menu_1.DownloadsMenu, { allowedTypes: allowedTypes, onUploadSuccess: handleFileUpload, onUploadError: handleUploadError, onLinkError: handleLinkError })),
        (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-downloads-field__body" },
            (0, element_1.createElement)(components_2.MediaUploader, { label: !Boolean(downloads.length) ? ((0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-downloads-field__drop-zone-content" },
                    (0, element_1.createElement)(upload_image_1.UploadImage, null),
                    (0, element_1.createElement)("p", { className: "wp-block-woocommerce-product-downloads-field__drop-zone-label" }, (0, element_1.createInterpolateElement)((0, i18n_1.__)('Supported file types: <Types /> and more. <link>View all</link>', 'woocommerce'), {
                        Types: ((0, element_1.createElement)(element_1.Fragment, null, "PNG, JPG, PDF, PPT, DOC, MP3, MP4")),
                        link: (
                        // eslint-disable-next-line jsx-a11y/anchor-has-content
                        (0, element_1.createElement)("a", { href: "https://codex.wordpress.org/Uploading_Files", target: "_blank", rel: "noreferrer", onClick: (event) => event.stopPropagation() })),
                    })))) : (''), buttonText: "", allowedMediaTypes: allowedTypes, multipleSelect: 'add', maxUploadFileSize: (_a = window.productBlockEditorSettings) === null || _a === void 0 ? void 0 : _a.maxUploadFileSize, onUpload: handleFileUpload, onFileUploadChange: handleFileUpload, onError: handleUploadError, additionalData: {
                    type: 'downloadable_product',
                } }),
            Boolean(downloads.length) && ((0, element_1.createElement)(components_2.Sortable, { className: "wp-block-woocommerce-product-downloads-field__table" }, downloads.map((download) => {
                const nameFromUrl = getFileName(download.file);
                const isUploading = download.file.startsWith('blob');
                return ((0, element_1.createElement)(components_2.ListItem, { key: download.file, className: "wp-block-woocommerce-product-downloads-field__table-row" },
                    (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-downloads-field__table-filename" },
                        (0, element_1.createElement)("span", null, download.name),
                        download.name !== nameFromUrl && ((0, element_1.createElement)("span", { className: "wp-block-woocommerce-product-downloads-field__table-filename-description" }, nameFromUrl))),
                    (0, element_1.createElement)("div", { className: "wp-block-woocommerce-product-downloads-field__table-actions" },
                        isUploading && ((0, element_1.createElement)(components_1.Spinner, { "aria-label": (0, i18n_1.__)('Uploading file', 'woocommerce') })),
                        !isUploading && ((0, element_1.createElement)(components_1.Button, { onClick: editHandler(download), variant: "tertiary" }, (0, i18n_1.__)('Edit', 'woocommerce'))),
                        (0, element_1.createElement)(components_1.Button, { icon: icons_1.closeSmall, label: (0, i18n_1.__)('Remove file', 'woocommerce'), disabled: isUploading, onClick: removeHandler(download) }))));
            })))),
        showManageDownloadLimitsModal && ((0, element_1.createElement)(manage_download_limits_modal_1.ManageDownloadLimitsModal, { initialValue: { downloadLimit, downloadExpiry }, onSubmit: handleManageDownloadLimitsModalSubmit, onClose: handleManageDownloadLimitsModalClose })),
        selectedDownload && ((0, element_1.createElement)(edit_downloads_modal_1.EditDownloadsModal, { downloadableItem: { ...selectedDownload }, onCancel: () => setSelectedDownload(null), onRemove: () => {
                removeDownload(selectedDownload);
                setSelectedDownload(null);
            }, onChange: (text) => {
                setSelectedDownload({
                    ...selectedDownload,
                    name: text,
                });
            }, onSave: editDownloadsModalSaveHandler(selectedDownload) }))));
}
exports.DownloadBlockEdit = DownloadBlockEdit;
