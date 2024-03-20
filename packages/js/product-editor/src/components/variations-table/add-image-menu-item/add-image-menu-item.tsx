/**
 * External dependencies
 */
import { MenuItem } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MediaUpload } from '@wordpress/media-utils';
import { recordEvent } from '@woocommerce/tracks';
import { ProductVariationImage } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { VariationActionsMenuItemProps } from '../types';
import { TRACKS_SOURCE } from '../../../constants';
import { mapUploadImageToImage } from '../../../utils/map-upload-image-to-image';

const DEFAULT_ALLOWED_MEDIA_TYPES = [ 'image' ];
const MODAL_CLASS_NAME = 'woocommerce-add-image-menu-item__upload_image_modal';
const MODAL_WRAPPER_CLASS_NAME =
	'woocommerce-add-image-menu-item__upload_image_modal_wrapper';

export function AddImageMenuItem( {
	selection,
	onChange,
	onClose,
}: VariationActionsMenuItemProps ) {
	const [ uploadFilesModalOpen, setUploadFilesModalOpen ] = useState( false );

	function handleMediaUploadSelect(
		value: {
			id?: number;
		} & Record< string, string >
	) {
		const ids = selection.map( ( { id } ) => id );
		const uploadedImage = mapUploadImageToImage(
			value
		) as ProductVariationImage;

		recordEvent( 'product_variations_menu_add_image', {
			source: TRACKS_SOURCE,
			action: 'add_image_to_variation',
			variation_id: ids,
		} );

		onChange(
			selection.map( ( { id } ) => ( {
				id,
				image: uploadedImage,
			} ) )
		);
		onClose();
	}

	function uploadFilesClickHandler( openMediaUploadModal: () => void ) {
		return function handleUploadFilesClick() {
			openMediaUploadModal();
			setUploadFilesModalOpen( true );
		};
	}

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

	return (
		<MediaUpload
			onSelect={ handleMediaUploadSelect }
			modalClass={ MODAL_CLASS_NAME }
			allowedTypes={ DEFAULT_ALLOWED_MEDIA_TYPES }
			multiple={ false }
			render={ ( { open } ) => (
				<MenuItem onClick={ uploadFilesClickHandler( open ) }>
					{ __( 'Add image', 'woocommerce' ) }
				</MenuItem>
			) }
		/>
	);
}
