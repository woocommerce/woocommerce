"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.DownloadsMenu = void 0;
/**
 * External dependencies
 */
const components_1 = require("@wordpress/components");
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
const insert_url_menu_item_1 = require("../insert-url-menu-item");
const upload_files_menu_item_1 = require("../upload-files-menu-item");
function DownloadsMenu({ allowedTypes, maxUploadFileSize, onUploadSuccess, onUploadError, onLinkError, }) {
    return ((0, element_1.createElement)(components_1.Dropdown
    // @ts-expect-error missing prop in types.
    , { 
        // @ts-expect-error missing prop in types.
        popoverProps: {
            placement: 'bottom-end',
        }, contentClassName: "woocommerce-downloads-menu__menu-content", renderToggle: ({ isOpen, onToggle }) => ((0, element_1.createElement)(components_1.Button, { "aria-expanded": isOpen, icon: isOpen ? icons_1.chevronUp : icons_1.chevronDown, variant: "secondary", onClick: onToggle, className: "woocommerce-downloads-menu__toggle" },
            (0, element_1.createElement)("span", null, (0, i18n_1.__)('Add new', 'woocommerce')))), renderContent: ({ onClose }) => ((0, element_1.createElement)("div", { className: "components-dropdown-menu__menu" },
            (0, element_1.createElement)(components_1.MenuGroup, null,
                (0, element_1.createElement)(upload_files_menu_item_1.UploadFilesMenuItem, { allowedTypes: allowedTypes, maxUploadFileSize: maxUploadFileSize, onUploadSuccess: (files) => {
                        onUploadSuccess(files);
                        onClose();
                    }, onUploadError: (error) => {
                        onUploadError(error);
                        onClose();
                    } }),
                (0, element_1.createElement)(insert_url_menu_item_1.InsertUrlMenuItem, { onLinkSuccess: (files) => {
                        onUploadSuccess(files);
                        onClose();
                    }, onLinkError: (error) => {
                        onLinkError(error);
                        onClose();
                    } })))) }));
}
exports.DownloadsMenu = DownloadsMenu;
