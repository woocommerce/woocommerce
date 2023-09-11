/**
 * External dependencies
 */
import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { createExtendedError } from '../utils/create-extended-error';

export type BackgroundRemovalParams = {
	imageFile: File;
	returnedImageType?: 'png' | 'jpg';
	returnedImageSize?: 'hd' | 'medium' | 'preview';
	token: string;
};

type BackgroundRemovalResponse = {
	loading: boolean;
	error: Error | null;
	imageData: Blob | null;
	fetchImage: ( params: BackgroundRemovalParams ) => void;
};

export const useBackgroundRemoval = (): BackgroundRemovalResponse => {
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState< Error | null >( null );
	const [ imageData, setImageData ] = useState< Blob | null >( null );

	const fetchImage = async ( params: BackgroundRemovalParams ) => {
		const { returnedImageType, returnedImageSize, imageFile, token } =
			params;

		if ( ! token ) {
			setError( createExtendedError( 'Invalid token', 'invalid_jwt' ) );
			return;
		}
		// Validate that the file is an image and has actual content.
		if ( ! imageFile.type.startsWith( 'image/' ) ) {
			setError(
				createExtendedError(
					'Invalid image file',
					'invalid_image_file'
				)
			);
			return;
		}

		const fileSizeInKB = params.imageFile.size / 1024;
		if ( fileSizeInKB < 5 ) {
			setError(
				createExtendedError(
					'Image file too small, must be at least 5KB',
					'invalid_image_file_size'
				)
			);
			return;
		}

		// The WPCOM REST API endpoint has a 10MB image size limit.
		if ( fileSizeInKB > 10240 ) {
			setError(
				createExtendedError(
					'Image file too large, must be under 10MB',
					'invalid_image_file_size'
				)
			);
			return;
		}

		// Set the image type when creating the blob.
		let imageType: string;
		switch ( returnedImageType ) {
			case 'png':
				imageType = 'image/png';
				break;
			case 'jpg':
			default:
				imageType = 'image/jpeg';
				break;
		}

		setLoading( true );
		const formData = new FormData();
		formData.append( 'image_file', imageFile );
		formData.append( 'returned_image_type', returnedImageType ?? 'jpg' );
		formData.append( 'returned_image_size', returnedImageSize ?? 'hd' );
		formData.append( 'token', token );

		try {
			const response = await apiFetch( {
				url: 'https://public-api.wordpress.com/wpcom/v2/ai-background-removal',
				method: 'POST',
				body: formData,
				headers: {
					'Content-Type': 'multipart/form-data',
				},
			} );

			const blob = new Blob( [ response as ArrayBuffer ], {
				type: imageType,
			} );
			setImageData( blob );
		} catch ( err ) {
			setError( err as Error );
		} finally {
			setLoading( false );
		}
	};

	return { loading, error, imageData, fetchImage };
};
