/**
 * External dependencies
 */

import { useEffect, useState } from 'react';
import { Modal, Button, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import './modal.scss';

/**
 * Upload image to WordPress Media Library.
 *
 * @param {Blob} imageBlob - The image data as a Blob.
 * @return {Promise<void>}
 */
const uploadImageToMediaLibrary = async (
	imageBlob: Blob
): Promise< void > => {
	// Create FormData object and append the Blob
	const formData = new FormData();
	formData.append( 'file', imageBlob, 'new-image.jpg' );

	try {
		// Upload image to WordPress Media Library
		const newMedia = await apiFetch( {
			path: '/wp/v2/media',
			method: 'POST',
			headers: {
				'Content-Disposition': 'attachment; filename=new-image.jpg',
			},
			body: formData,
		} );

		console.log( 'Successfully uploaded:', newMedia );
	} catch ( error ) {
		console.error( 'Failed to upload image:', error );
	}
};

/**
 * Image Variation Modal Component
 *
 * @return {JSX.Element} The modal component.
 */
const ImageVariationModal: React.FC = () => {
	const [ isOpen, setIsOpen ] = useState( true );
	const [ isLoading, setLoading ] = useState( false );
	const [ newImage, setNewImage ] = useState< Blob | null >( null );
	const [ newImageUrl, setNewImageUrl ] = useState< string | null >( null );

	useEffect( () => {
		if ( newImage ) {
			const reader = new FileReader();
			reader.onloadend = () => {
				setNewImageUrl( reader.result as string );
			};
			reader.readAsDataURL( newImage );
		}
	}, [ newImage ] );

	/**
	 * Generate image variations.
	 */
	const generateVariations = async (): Promise< void > => {
		setLoading( true );
		try {
			const response = ( await apiFetch( {
				/* @todo: Get site URL dynamically for this request. */
				/* @todo: Get a JWT for this request using the jetpack AI JWT package. */
				path: 'https://public-api.wordpress.com/wpcom/v2/sites/wooai.jurassic.tube/ai-image',
				method: 'POST',
				parse: false, // Do not parse the response as JSON
			} ) ) as Blob;

			setNewImage( response );
		} catch ( error ) {
			console.error( 'Failed to generate variations:', error );
		}
		setLoading( false );
	};

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Upload new image to media library and close modal.
	 *
	 * @return {void}
	 */
	const acceptImage = (): void => {
		// Logic to upload newImage to media library can go here
		// throw error if !newImage ?
		if ( newImage ) {
			uploadImageToMediaLibrary( newImage );
		}
		wp.media.frame.modal.open();
		setIsOpen( false ); // Close the modal
	};

	const handleClose = () => {
		setIsOpen( false );
		wp.media.frame.modal.open();
	};

	return (
		<div className="background-image-replacer">
			<Modal
				title="Generate Image Variations"
				onRequestClose={ handleClose }
			>
				<div>
					{ /* Original Image */ }
					{ /* @todo: Get image URL from selected image in media library */ }
					<img src="original_image_url" alt="Original" />
					{ /* New Image */ }
					{ newImageUrl && <img src={ newImageUrl } alt="New" /> }
					{ /* Loading Indicator */ }
					{ isLoading && <Spinner /> }
				</div>
				<div>
					<Button onClick={ generateVariations }>
						Generate Variations
					</Button>
					{ newImage && (
						<Button isDestructive onClick={ acceptImage }>
							Accept
						</Button>
					) }
				</div>
			</Modal>
		</div>
	);
};

export default ImageVariationModal;
