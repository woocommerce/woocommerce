/**
 * External dependencies
 */
import React, { FC, useState, useRef } from 'react';

declare global {
	interface Window {
		wcPhotoRoomKey: string;
	}
}

const ImageProcessor: FC = () => {
	const [ processing, setProcessing ] = useState( false );
	const [ processedImage, setProcessedImage ] = useState< string | null >(
		null
	);
	const fileInput = useRef< HTMLInputElement | null >( null );

	const processImage = async () => {
		if ( fileInput.current?.files && fileInput.current.files.length > 0 ) {
			const file = fileInput.current.files[ 0 ];
			const formData = new FormData();

			formData.append( 'image_file', file );
			formData.append( 'bg_color', 'green' );
			formData.append( 'size', 'preview' );

			setProcessing( true );

			const response = await fetch(
				'https://sdk.photoroom.com/v1/segment',
				{
					method: 'POST',
					body: formData,
					headers: {
						'x-api-key': window.wcPhotoRoomKey,
					},
				}
			);

			if ( ! response.ok ) {
				throw new Error( 'Network response was not ok' );
			}

			const blob = await response.blob();
			const objectURL = URL.createObjectURL( blob );
			setProcessedImage( objectURL );
			setProcessing( false );
		}
	};

	return (
		<div>
			<input type="file" accept="image/*" ref={ fileInput } />
			<button onClick={ processImage } disabled={ processing }>
				Process image
			</button>
			{ processedImage && <img src={ processedImage } alt="Processed" /> }
		</div>
	);
};

export default ImageProcessor;
