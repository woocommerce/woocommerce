/**
 * External dependencies
 */
import { MenuItem } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { media } from '@wordpress/icons';
import { MediaItem } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { MediaLibraryMenuItemProps } from './types';
import { MediaLibrary } from '../media-library';

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

	function handleMediaUploadSelect( value: MediaItem[] ) {
		onUploadSuccess( value );
	}

	function uploadFilesClickHandler( openMediaUploadModal: () => void ) {
		return function handleUploadFilesClick() {
			openMediaUploadModal();
			setUploadFilesModalOpen( true );
		};
	}

	return (
		<MediaLibrary
			className={ MODAL_CLASS_NAME }
			allowedTypes={ allowedTypes }
			multiple="add"
			uploaderParams={ {
				type: 'downloadable_product',
			} }
			onSelect={ handleMediaUploadSelect }
		>
			{ ( { open } ) => (
				<MenuItem
					icon={ media }
					iconPosition="left"
					onClick={ uploadFilesClickHandler( open ) }
					info={ __( 'Choose from uploaded media', 'woocommerce' ) }
				>
					{ __( 'Media Library', 'woocommerce' ) }
				</MenuItem>
			) }
		</MediaLibrary>
	);
}
