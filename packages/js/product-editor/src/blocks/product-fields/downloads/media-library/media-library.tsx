/**
 * External dependencies
 */
import { useEffect, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MediaLibraryProps } from './types';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
declare const wp: any;

export function MediaLibrary( {
	allowedTypes,
	modalTitle,
	modalButtonText,
	multiple,
	className,
	uploaderParams,
	children,
	onSelect,
}: MediaLibraryProps ) {
	const mediaLibraryModal = useMemo(
		function createMediaLibraryModal() {
			const media = wp.media( {
				title: modalTitle,
				library: {
					type: allowedTypes,
				},
				button: {
					text: modalButtonText,
				},
				multiple,
				states: [
					new wp.media.controller.Library( {
						title: modalTitle,
						library: wp.media.query(),
						multiple,
						priority: 20,
						filterable: 'all',
					} ),
				],
			} );

			return media;
		},
		[ allowedTypes, modalTitle, modalButtonText, multiple ]
	);

	useEffect(
		function initializeEvents() {
			function handleSelect() {
				const mediaItems = mediaLibraryModal
					.state()
					.get( 'selection' )
					.toJSON();

				onSelect( mediaItems );
			}

			function handleReady() {
				mediaLibraryModal.uploader.options.uploader.params =
					uploaderParams;
			}

			mediaLibraryModal.on( 'select', handleSelect );
			mediaLibraryModal.on( 'ready', handleReady );

			return function unmountMediaLibraryModal() {
				mediaLibraryModal.off( 'select', handleSelect );
				mediaLibraryModal.off( 'ready', handleReady );
			};
		},
		[ mediaLibraryModal, uploaderParams, onSelect ]
	);

	useEffect(
		() =>
			function unmountMediaLibraryModal() {
				mediaLibraryModal.remove();
			},
		[ mediaLibraryModal ]
	);

	function openMediaLibraryModal() {
		mediaLibraryModal.$el.addClass( className );
		mediaLibraryModal.open();
	}

	return children( {
		open: openMediaLibraryModal,
	} );
}
