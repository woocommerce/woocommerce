/**
 * External dependencies
 */
import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { createExtendedError } from '../utils/create-extended-error';
import { requestJetpackToken } from '../utils/requestJetpackToken';

export type BackgroundRemovalParams = {
	imageFile: File;
	returnedImageType?: 'png' | 'jpeg';
	returnedImageSize?: 'hd' | 'medium' | 'preview';
};

type BackgroundRemovalResponse = {
	loading: boolean;
	imageData: Blob | null;
	fetchImage: ( params: BackgroundRemovalParams ) => void;
};

export const useBackgroundRemoval = (): BackgroundRemovalResponse => {
	const [ loading, setLoading ] = useState( false );
	const [ imageData, setImageData ] = useState< Blob | null >( null );

	const fetchImage = async (
		params: BackgroundRemovalParams
	): Promise< Blob | undefined > => {
		setLoading( true );
		const { returnedImageType, returnedImageSize, imageFile } = params;
		const { token } = await requestJetpackToken();

		if ( ! token ) {
			throw createExtendedError( 'Invalid token', 'invalid_jwt' );
		}
		// Validate that the file is an image and has actual content.
		if ( ! imageFile.type.startsWith( 'image/' ) ) {
			throw createExtendedError(
				'Invalid image file',
				'invalid_image_file'
			);
			return;
		}

		const fileSizeInKB = params.imageFile.size / 1024;
		if ( fileSizeInKB < 5 ) {
			throw createExtendedError(
				'Image file too small, must be at least 5KB',
				'image_file_too_small'
			);
			return;
		}

		// The WPCOM REST API endpoint has a 10MB image size limit.
		if ( fileSizeInKB > 10240 ) {
			throw createExtendedError(
				'Image file too large, must be under 10MB',
				'image_file_too_large'
			);
			return;
		}

		const imageType = returnedImageType ?? 'jpeg';

		const formData = new FormData();
		formData.append( 'image_file', imageFile );
		formData.append( 'returned_image_type', imageType );
		formData.append( 'returned_image_size', returnedImageSize ?? 'hd' );
		formData.append( 'token', token );

		try {
			const response = await apiFetch( {
				url: 'https://public-api.wordpress.com/wpcom/v2/ai-background-removal',
				method: 'POST',
				body: formData,
				parse: false,
				credentials: 'omit',
			} );

			const blob = await (
				response as { blob: () => Promise< Blob > }
			 ).blob();
			setImageData( blob );
			return blob;
		} catch ( err ) {
			throw err;
		} finally {
			setLoading( false );
		}
	};

	return { loading, imageData, fetchImage };
};
