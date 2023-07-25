/**
 * External dependencies
 */
import React, { FC, useState, useRef } from 'react';
import { css } from '@emotion/react';

declare global {
	interface Window {
		wcPhotoRoomKey: string;
	}
}

const imageContainerStyle = css( {
	marginTop: '20px',
} );

const controlsStyle = css( {
	marginTop: '20px',
} );

const containerStyle = css( {
	minHeight: '500px',
	display: 'flex',
	alignItems: 'center',
	flexDirection: 'column',
} );

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
			formData.append( 'size', 'preview' );
			formData.append( 'crop', 'true' );

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
		<div css={ containerStyle }>
			<div css={ controlsStyle }>
				<input type="file" accept="image/*" ref={ fileInput } />
				<button onClick={ processImage } disabled={ processing }>
					Process image
				</button>
			</div>
			<div css={ imageContainerStyle }>
				{ processedImage && (
					<img src={ processedImage } alt="Processed" />
				) }
			</div>
		</div>
	);
};

export default ImageProcessor;
