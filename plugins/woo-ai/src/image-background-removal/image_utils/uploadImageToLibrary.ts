/**
 * External dependencies
 */
import { createExtendedError } from '@woocommerce/ai';

type BackgroundRemovalParams = {
	imageBlob: Blob;
	libraryFilename: string;
};

declare global {
	interface Window {
		ajaxurl?: string;
	}
}

export const uploadImageToLibrary = async ( {
	imageBlob,
	libraryFilename,
}: BackgroundRemovalParams ) => {
	const fileObj = new File( [ imageBlob ], libraryFilename );
	let _fileId: number | null = null;

	await wp.mediaUtils.uploadMedia( {
		allowedTypes: 'image',
		filesList: [ fileObj ],
		onError: ( e: Error ) => {
			throw e;
		},
		onFileChange: ( files: Array< { id: number } > ) => {
			if ( files.length > 0 && files[ 0 ]?.id ) {
				_fileId = files[ 0 ].id;
			}
		},
	} );

	if ( _fileId === null ) {
		return;
	}

	const nonceValue = window?.JP_CONNECTION_INITIAL_STATE.apiNonce ?? '';
	const ajaxUrl = window?.ajaxurl;

	if ( ! ( ajaxUrl && nonceValue ) ) {
		throw createExtendedError(
			'Missing nonce or ajaxurl',
			'missing_nonce'
		);
	}

	const formData = new FormData();

	formData.append( 'action', 'get-attachment' );
	formData.append( 'id', String( _fileId ) );
	formData.append( '_ajax_nonce', nonceValue );

	const response = await fetch( ajaxUrl, {
		method: 'POST',
		body: formData,
	} ).then( ( res ) => res.json() );

	if ( ! response.data ) {
		throw createExtendedError(
			'Invalid response from ajax request',
			'invalid_response'
		);
	}

	const attachmentData = wp.media.model.Attachment.create( {
		...response.data,
		file: fileObj,
		uploading: false,
		date: new Date(),
		filename: fileObj.name,
		menuOrder: 0,
		type: 'image',
		uploadedTo: wp.media.model.settings.post.id,
	} );

	wp.media.model.Attachment.get( _fileId, attachmentData );
	wp.Uploader.queue.add( attachmentData );
	wp.Uploader.queue.reset();
};
