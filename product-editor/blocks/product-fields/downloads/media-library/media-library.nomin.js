"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.MediaLibrary = void 0;
/**
 * External dependencies
 */
const element_1 = require("@wordpress/element");
function MediaLibrary({ allowedTypes, modalTitle, modalButtonText, multiple, className, uploaderParams, children, onSelect, }) {
    const mediaLibraryModal = (0, element_1.useMemo)(function createMediaLibraryModal() {
        const media = wp.media({
            title: modalTitle,
            library: {
                type: allowedTypes,
            },
            button: {
                text: modalButtonText,
            },
            multiple,
            states: [
                new wp.media.controller.Library({
                    title: modalTitle,
                    library: wp.media.query(),
                    multiple,
                    priority: 20,
                    filterable: 'all',
                }),
            ],
        });
        return media;
    }, [allowedTypes, modalTitle, modalButtonText, multiple]);
    (0, element_1.useEffect)(function initializeEvents() {
        function handleSelect() {
            const mediaItems = mediaLibraryModal
                .state()
                .get('selection')
                .toJSON();
            onSelect(mediaItems);
        }
        function handleReady() {
            mediaLibraryModal.uploader.options.uploader.params =
                uploaderParams;
        }
        mediaLibraryModal.on('select', handleSelect);
        mediaLibraryModal.on('ready', handleReady);
        return function unmountMediaLibraryModal() {
            mediaLibraryModal.off('select', handleSelect);
            mediaLibraryModal.off('ready', handleReady);
        };
    }, [mediaLibraryModal, uploaderParams, onSelect]);
    (0, element_1.useEffect)(() => function unmountMediaLibraryModal() {
        mediaLibraryModal.remove();
    }, [mediaLibraryModal]);
    function openMediaLibraryModal() {
        mediaLibraryModal.$el.addClass(className);
        mediaLibraryModal.open();
    }
    return children({
        open: openMediaLibraryModal,
    });
}
exports.MediaLibrary = MediaLibrary;
