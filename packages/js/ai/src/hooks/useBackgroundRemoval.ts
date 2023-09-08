/**
 * External dependencies
 */
import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

export type BackgroundRemovalParams = {
	imageFile: File;
	returnPngImage?: boolean;
	returnedImageSize?: string;
	token: string;
};

type BackgroundRemovalResponse = {
	loading: boolean;
	error: Error | null;
	imageData: Blob | null;
	fetchImage: ( params: BackgroundRemovalParams ) => void;
};

const useBackgroundRemoval = (): BackgroundRemovalResponse => {
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState< Error | null >( null );
	const [ imageData, setImageData ] = useState< Blob | null >( null );

	const fetchImage = async ( params: BackgroundRemovalParams ) => {
		const { returnPngImage, returnedImageSize, imageFile, token } = params;

		if ( ! token ) {
			setError( new Error( 'Invalid token' ) );
			return;
		}
		// Validate that the file is an image and has actual content.
		if ( ! imageFile.type.startsWith( 'image/' ) ) {
			setError( new Error( 'Invalid image file' ) );
			return;
		}

		const fileSizeInKB = params.imageFile.size / 1024;
		if ( fileSizeInKB < 5 ) {
			setError(
				new Error( 'Image file too small, must be at least 5KB' )
			);
			return;
		}

		// The WPCOM REST API endpoint has a 10MB image size limit.
		if ( fileSizeInKB > 10240 ) {
			setError( new Error( 'Image file too large, must be under 10MB' ) );
			return;
		}

		setLoading( true );
		const formData = new FormData();
		formData.append( 'image_file', imageFile );
		formData.append( 'return_png_image', returnPngImage ? 'yes' : 'no' );
		formData.append( 'returned_image_size', returnedImageSize || '' );
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
			const imageType = returnPngImage ? 'image/png' : 'image/jpeg';
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

export default useBackgroundRemoval;
