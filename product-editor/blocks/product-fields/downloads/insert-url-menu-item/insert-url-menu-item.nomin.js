"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.InsertUrlMenuItem = void 0;
const element_1 = require("@wordpress/element");
const i18n_1 = require("@wordpress/i18n");
const icons_1 = require("@wordpress/icons");
const components_1 = require("@wordpress/components");
function validateInput(input) {
    input.required = true;
    input.setCustomValidity('');
    if (input.validity.valueMissing) {
        input.setCustomValidity((0, i18n_1.__)('The URL is required', 'woocommerce'));
    }
    if (input.validity.typeMismatch) {
        input.setCustomValidity((0, i18n_1.__)('Insert a valid URL', 'woocommerce'));
    }
}
function InsertUrlMenuItem({ onLinkSuccess, onLinkError, }) {
    function handleSubmit(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const urlInput = form.url;
        validateInput(urlInput);
        if (form.checkValidity()) {
            const url = form.url.value;
            const mediaItem = {
                url,
            };
            onLinkSuccess([mediaItem]);
        }
        else {
            onLinkError(urlInput.validationMessage);
        }
    }
    function handleInput(event) {
        const urlInput = event.target;
        validateInput(urlInput);
    }
    function handleBlur(event) {
        const urlInput = event.target;
        validateInput(urlInput);
    }
    return ((0, element_1.createElement)(components_1.Dropdown
    // @ts-expect-error missing prop in types.
    , { 
        // @ts-expect-error missing prop in types.
        popoverProps: {
            placement: 'left',
        }, renderToggle: ({ isOpen, onToggle }) => ((0, element_1.createElement)(components_1.MenuItem, { "aria-expanded": isOpen, icon: icons_1.customLink, iconPosition: "left", onClick: onToggle, info: (0, i18n_1.__)('Link to a file hosted elsewhere', 'woocommerce') }, (0, i18n_1.__)('Insert from URL', 'woocommerce'))), renderContent: () => ((0, element_1.createElement)("form", { className: "components-dropdown-menu__menu", noValidate: true, onSubmit: handleSubmit },
            (0, element_1.createElement)(components_1.__experimentalInputControl, { name: "url", type: "url", placeholder: (0, i18n_1.__)('Insert URL', 'woocommerce'), suffix: (0, element_1.createElement)(components_1.Button, { icon: icons_1.keyboardReturn, type: "submit" }), className: "woocommerce-inert-url-menu-item__input", "aria-label": (0, i18n_1.__)('Insert URL', 'woocommerce'), onInput: handleInput, onBlur: handleBlur }))) }));
}
exports.InsertUrlMenuItem = InsertUrlMenuItem;
