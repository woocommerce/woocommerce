"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.EditDownloadsModal = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const element_1 = require("@wordpress/element");
const icons_1 = require("@wordpress/icons");
const data_1 = require("@wordpress/data");
const tracks_1 = require("@woocommerce/tracks");
const components_1 = require("@woocommerce/components");
const components_2 = require("@wordpress/components");
const union_icon_1 = require("./images/union-icon");
const downloads_custom_image_1 = require("./images/downloads-custom-image");
const EditDownloadsModal = ({ downloadableItem, onCancel, onChange, onRemove, onSave, }) => {
    const { createNotice } = (0, data_1.useDispatch)('core/notices');
    const [isCopingToClipboard, setIsCopingToClipboard] = (0, element_1.useState)(false);
    const { id = 0, file = '', name = '' } = downloadableItem;
    const onCopySuccess = () => {
        createNotice('success', (0, i18n_1.__)('URL copied successfully.', 'woocommerce'));
    };
    const isImage = (filename = '') => {
        if (!filename)
            return;
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const fileExtension = (filename.split('.').pop() || '').toLowerCase();
        return imageExtensions.includes(fileExtension);
    };
    async function copyTextToClipboard(text) {
        if ('clipboard' in navigator) {
            await navigator.clipboard.writeText(text);
        }
        else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
        await onCopySuccess();
    }
    async function handleCopyToClipboard() {
        (0, tracks_1.recordEvent)('product_downloads_modal_copy_url_to_clipboard');
        setIsCopingToClipboard(true);
        await copyTextToClipboard(file);
        setIsCopingToClipboard(false);
    }
    return ((0, element_1.createElement)(components_2.Modal, { title: (0, i18n_1.sprintf)(
        /* translators: %s is the attribute name */
        (0, i18n_1.__)('Edit %s', 'woocommerce'), name), onRequestClose: (event) => {
            if (!event.isPropagationStopped() && !isCopingToClipboard) {
                (0, tracks_1.recordEvent)('product_downloads_modal_cancel');
                onCancel();
            }
        }, className: "woocommerce-edit-downloads-modal" },
        (0, element_1.createElement)("div", { className: "woocommerce-edit-downloads-modal__preview" },
            (0, element_1.createElement)(components_1.ImageGallery, { allowDragging: false, columns: 1 }, isImage(file) ? ((0, element_1.createElement)(components_1.ImageGalleryItem, { key: id, alt: name, src: file, id: `${id}`, isCover: false })) : ((0, element_1.createElement)(downloads_custom_image_1.DownloadsCustomImage, null))),
            (0, element_1.createElement)("div", { className: "components-form-file-upload" },
                (0, element_1.createElement)("p", null, name))),
        (0, element_1.createElement)(components_2.BaseControl, { id: 'file-name-help', className: "woocommerce-edit-downloads-modal__file-name", help: (0, i18n_1.__)('Your customers will see this on the thank-you page and in their order confirmation email.', 'woocommerce') },
            (0, element_1.createElement)(components_2.__experimentalInputControl, { id: 'file-name', label: (0, i18n_1.__)('FILE NAME', 'woocommerce'), name: 'file-name', value: name || '', onChange: onChange })),
        (0, element_1.createElement)("div", { className: "woocommerce-edit-downloads-modal__file-url" },
            (0, element_1.createElement)(components_2.__experimentalInputControl, { disabled: true, id: 'file-url', label: (0, i18n_1.__)('FILE URL', 'woocommerce'), name: 'file-url', value: file || '', suffix: (0, element_1.createElement)(components_2.Button, { icon: (0, element_1.createElement)(union_icon_1.UnionIcon, null), onClick: handleCopyToClipboard }) })),
        (0, element_1.createElement)("div", { className: "woocommerce-edit-downloads-modal__buttons" },
            (0, element_1.createElement)("div", { className: "woocommerce-edit-downloads-modal__buttons-left" },
                (0, element_1.createElement)(components_2.Button, { icon: icons_1.trash, isDestructive: true, variant: "tertiary", label: (0, i18n_1.__)('Delete', 'woocommerce'), onClick: () => {
                        (0, tracks_1.recordEvent)('product_downloads_modal_delete');
                        onRemove();
                    } }, (0, i18n_1.__)('Delete file', 'woocommerce'))),
            (0, element_1.createElement)("div", { className: "woocommerce-edit-downloads-modal__buttons-right" },
                (0, element_1.createElement)(components_2.Button, { label: (0, i18n_1.__)('Cancel', 'woocommerce'), onClick: () => {
                        (0, tracks_1.recordEvent)('product_downloads_modal_cancel');
                        onCancel();
                    }, variant: "tertiary" }, (0, i18n_1.__)('Cancel', 'woocommerce')),
                (0, element_1.createElement)(components_2.Button, { label: (0, i18n_1.__)('Update', 'woocommerce'), onClick: () => {
                        (0, tracks_1.recordEvent)('product_downloads_modal_update');
                        onSave();
                    }, variant: "primary" }, (0, i18n_1.__)('Update', 'woocommerce'))))));
};
exports.EditDownloadsModal = EditDownloadsModal;
