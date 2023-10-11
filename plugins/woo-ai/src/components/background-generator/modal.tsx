/**
 * External dependencies
 */

import { useState } from 'react';
import { Modal, Button, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import './modal.scss';

/**
 * Image Variation Modal Component
 *
 * @return {JSX.Element} The modal component.
 */
const ImageVariationModal: React.FC = () => {
	const [ isOpen, setIsOpen ] = useState( true );
	const [ isLoading, setLoading ] = useState( false );
	const [ newImage, setNewImage ] = useState< string | null >( null );

	/**
	 * Generate image variations.
	 *
	 * @return {Promise<void>}
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

			// Convert Blob to Data URL for displaying in an <img> element
			const reader = new FileReader();
			reader.onloadend = () => {
				setNewImage( reader.result as string );
			};
			reader.readAsDataURL( response );
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
		console.log( 'acceptImage' );
		// Logic to upload newImage to media library can go here
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
					{ newImage && <img src={ newImage } alt="New" /> }
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
