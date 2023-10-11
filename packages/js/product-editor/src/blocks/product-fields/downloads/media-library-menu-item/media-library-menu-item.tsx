/**
 * External dependencies
 */

import { MenuItem } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { media } from '@wordpress/icons';
import { MediaItem, MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { MediaLibraryMenuItemProps } from './types';

const MODAL_CLASS_NAME =
	'woocommerce-media-library-menu-item__upload_files_modal';
const MODAL_WRAPPER_CLASS_NAME =
	'woocommerce-media-library-menu-item__upload_files_modal_wrapper';

export function MediaLibraryMenuItem( {
	allowedTypes,
	onUploadSuccess,
}: MediaLibraryMenuItemProps ) {
	const [ uploadFilesModalOpen, setUploadFilesModalOpen ] = useState( false );

	useEffect(
		function addUploadModalClass() {
			const modal = document.querySelector( `.${ MODAL_CLASS_NAME }` );
			const dialog = modal?.closest( '[role="dialog"]' );
			const wrapper = dialog?.parentElement;

			wrapper?.classList.add( MODAL_WRAPPER_CLASS_NAME );

			return () => {
				wrapper?.classList.remove( MODAL_WRAPPER_CLASS_NAME );
			};
		},
		[ uploadFilesModalOpen ]
	);

	function handleMediaUploadSelect( value: unknown ) {
		onUploadSuccess( value as MediaItem[] );
	}

	function uploadFilesClickHandler( openMediaUploadModal: () => void ) {
		return function handleUploadFilesClick() {
			openMediaUploadModal();
			setUploadFilesModalOpen( true );
		};
	}

	return (
		<MediaUpload
			modalClass={ MODAL_CLASS_NAME }
			onSelect={ handleMediaUploadSelect }
			allowedTypes={ allowedTypes }
			// @ts-expect-error - TODO multiple also accepts string.
			multiple={ 'add' }
			render={ ( { open } ) => (
				<MenuItem
					icon={ media }
					iconPosition="left"
					onClick={ uploadFilesClickHandler( open ) }
					info={ __( 'Choose from uploaded media', 'woocommerce' ) }
				>
					{ __( 'Media Library', 'woocommerce' ) }
				</MenuItem>
			) }
		/>
	);
}
