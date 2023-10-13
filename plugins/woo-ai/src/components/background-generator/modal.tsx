/**
 * External dependencies
 */

import { useEffect, useState } from 'react';
import {
	Modal,
	Button,
	Spinner,
	TextareaControl,
	TabPanel,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __experimentalRequestJetpackToken as requestJetpackToken, __experimentalUseBackgroundRemoval as useBackgroundRemoval } from '@woocommerce/ai';

/**
 * Internal dependencies
 */
import './modal.scss';
import {
	uploadImageToLibrary,
	getCurrentAttachmentDetails,
} from '../../image-background-removal/image_utils';
import { BackgroundProductGenerator } from '../background-product-generator';

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
	const [ originalImage, setOriginalImage ] = useState< string >( '' );
	const [ newImage, setNewImage ] = useState< Blob | null >( null );
	const [ bgRemovedImage, setBgRemovedImage ] = useState< Blob | null >( null );
	const [ newImageUrl, setNewImageUrl ] = useState< string | null >( null );
	const [ imagePrompt, setImagePrompt ] = useState(
		'Sandy beach on a sunny day.'
	);
	const { fetchImage } = useBackgroundRemoval();

	useEffect( () => {
		if ( newImage ) {
			const reader = new FileReader();
			reader.onloadend = () => {
				setNewImageUrl( reader.result as string );
			};
			reader.readAsDataURL( newImage );
		}
	}, [ newImage ] );

	useEffect( () => {
		const { url: imgUrl, filename } = getCurrentAttachmentDetails();
		if ( ! imgUrl ) {
			return;
		}
		setOriginalImage( imgUrl );
		console.log( 'original', imgUrl );

		fetch( imgUrl ).then( ( res ) =>
			res.blob()
		).then( ( originalBlob ) => {
			console.log( 'original image blob', originalBlob );
			const blob = fetchImage( {
				imageFile: new File( [ originalBlob ], filename ?? '', {
					type: originalBlob.type.length > 1 ? originalBlob.type : 'image/jpeg',
				} ),
				backgroundColor: '0,0,0,0',
			} );
			return blob;
		} ).then( blob => {
			console.log( 'background removed blob received' );
			setBgRemovedImage( blob );
		} );
	}, [] );

	/**
	 * Generate image variations.
	 */
	const generateVariations = async (): Promise< void > => {
		setLoading( true );
		const blogId =
			window?.JP_CONNECTION_INITIAL_STATE?.userConnectionData?.currentUser
				?.blogId;

		const { token } = await requestJetpackToken();

		if ( ! token ) {
			throw Error( 'Invalid token' );
		}

		const formData = new FormData();
		formData.append( 'prompt', imagePrompt );
		formData.append( 'token', token );

		try {
			const response = await apiFetch( {
				/* @todo: Get site URL dynamically for this request. */
				/* @todo: Get a JWT for this request using the jetpack AI JWT package. */
				url: `https://public-api.wordpress.com/wpcom/v2/sites/${ blogId }/ai-image`,
				method: 'POST',
				parse: false, // Do not parse the response as JSON
				body: formData,
				credentials: 'omit',
			} );

			const blob = await (
				response as { blob: () => Promise< Blob > }
			 ).blob();

			setNewImage( blob );
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
		if ( bgRemovedImage ) {
			uploadImageToMediaLibrary( bgRemovedImage );
		}
		wp.media.frame.modal.open();
		setIsOpen( false ); // Close the modal
	};

	const handleClose = () => {
		setIsOpen( false );
		wp.media.frame.modal.open();
	};
	const objectURL = bgRemovedImage ? URL.createObjectURL( bgRemovedImage ) : null;

	const tabs = [
		{
			name: 'original',
			title: 'Original Image',
			view: (
				<div>
					{ /* @todo: Replace with the actual URL of the original image */ }
					<img src={ originalImage } alt="Original" />
				</div>
			),
		},
		{
			name: 'remove-bg',
			title: 'Remove Background',
			view: (
				<div>
					<img
						src={ objectURL ?? '' }
						alt="Background Removed"
					/>
					<Button isPrimary
						onClick = { () => acceptImage() }
					>
						Accept and Add to Media Library
					</Button>
				</div>
			),
		},
		{
			name: 'magic-bg',
			title: 'Magic Background',
			view: (
				<div>
					<div className="image-variation-modal__canvas-container">
						{ newImage && bgRemovedImage && ( <BackgroundProductGenerator
							className="image-variation-modal__canvas"
							backgroundImageSrc={ newImage }
							productImageSrc={ bgRemovedImage }
						/> ) }
					</div>
					<TextareaControl
						label="Prompt for Stable Diffusion"
						value={ imagePrompt }
						onChange={ ( newPrompt ) =>
							setImagePrompt( newPrompt )
						}
					/>
					<Button isPrimary onClick={ generateVariations }>
						Generate Magic Background
					</Button>
				</div>
			),
		},
	];

	return (
		<div className="background-image-replacer">
			<Modal
				title="Generate Image Variations"
				onRequestClose={ handleClose }
			>
				<TabPanel className="my-tab-panel" tabs={ tabs }>
					{ ( tab ) => <div>{ tab.view }</div> }
				</TabPanel>
			</Modal>
		</div>
	);
};

export default ImageVariationModal;
