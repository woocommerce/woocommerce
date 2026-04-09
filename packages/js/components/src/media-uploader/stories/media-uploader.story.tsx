/**
 * External dependencies
 */
import { createElement } from 'react';
import { Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { cloudUpload } from '@wordpress/icons';
import type { Attachment } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import { MediaUploader } from '../';
import { MockMediaUpload } from './mock-media-uploader';

const ImageGallery = ( { images }: { images: Attachment[] } ) => {
	return (
		<div style={ { marginBottom: '16px' } }>
			{ images.map( ( image, index ) => {
				return (
					<img
						key={ index }
						alt={ image.alt }
						src={ image.url }
						style={ {
							maxWidth: '100px',
							marginRight: '16px',
						} }
					/>
				);
			} ) }
		</div>
	);
};

const readImage = ( file: Blob ) => {
	return new Promise< Attachment >( ( resolve ) => {
		const fileReader = new FileReader();
		fileReader.onload = function ( event ) {
			const image = {
				alt: 'Temporary image',
				url: event?.target?.result,
			} as Attachment;
			resolve( image );
		};
		fileReader.readAsDataURL( file );
	} );
};

const mockUploadMedia = async ( {
	filesList,
	onFileChange,
}: {
	filesList: File[];
	onFileChange?: ( files: Partial< Attachment >[] ) => void;
} ) => {
	// The values sent by the FormFileUpload and the DropZone components are different.
	// This is why we need to transform everything into an array.
	const images = await Promise.all(
		filesList.map( ( file ) => {
			if ( typeof file === 'object' ) {
				return readImage( file );
			}
			return {};
		} )
	);
	onFileChange?.( images as Partial< Attachment >[] );
};

export const Basic = () => {
	const [ images, setImages ] = useState< Attachment[] >( [] );

	return (
		<>
			<ImageGallery images={ images } />
			<MediaUploader
				MediaUploadComponent={ MockMediaUpload }
				onSelect={ ( file ) =>
					setImages( [ ...images, file as Attachment ] )
				}
				onError={ () => null }
				onFileUploadChange={ ( files ) =>
					setImages( [
						...images,
						...( Array.isArray( files ) ? files : [ files ] ),
					] )
				}
				onUpload={ ( files ) =>
					setImages( [
						...images,
						...( Array.isArray( files ) ? files : [ files ] ),
					] )
				}
				uploadMedia={ mockUploadMedia }
			/>
		</>
	);
};

export const DisabledDropZone = () => {
	const [ images, setImages ] = useState< Attachment[] >( [] );

	return (
		<>
			<ImageGallery images={ images } />
			<MediaUploader
				hasDropZone={ false }
				label={ 'Click the button below to upload' }
				MediaUploadComponent={ MockMediaUpload }
				onFileUploadChange={ ( files ) =>
					setImages( [
						...images,
						...( Array.isArray( files ) ? files : [ files ] ),
					] )
				}
				onSelect={ ( file ) =>
					setImages( [ ...images, file as Attachment ] )
				}
				onError={ () => null }
				uploadMedia={ mockUploadMedia }
			/>
		</>
	);
};

export const MaxUploadFileSize = () => {
	const [ error, setError ] = useState< string | null >( null );

	return (
		<>
			{ error && (
				<Notice isDismissible={ false } status={ 'error' }>
					{ error }
				</Notice>
			) }

			<MediaUploader
				maxUploadFileSize={ 1000 }
				MediaUploadComponent={ MockMediaUpload }
				onSelect={ () => null }
				onError={ ( e ) => setError( e.message ) }
				onUpload={ () => null }
			/>
		</>
	);
};

export const ButtonWithOnlyIcon = () => {
	const [ error, setError ] = useState< string | null >( null );

	return (
		<>
			{ error && (
				<Notice isDismissible={ false } status={ 'error' }>
					{ error }
				</Notice>
			) }

			<MediaUploader
				maxUploadFileSize={ 1000 }
				buttonProps={ {
					icon: cloudUpload,
					iconSize: 32,
					variant: 'tertiary',
					'aria-label': 'Upload media',
				} }
				buttonText=""
				MediaUploadComponent={ MockMediaUpload }
				onSelect={ () => null }
				onError={ ( e ) => setError( e.message ) }
				onUpload={ () => null }
			/>
		</>
	);
};

export default {
	title: 'Components/MediaUploader',
	component: Basic,
};
