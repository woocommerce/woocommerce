/**
 * External dependencies
 */
import React, { createElement } from 'react';
import { Notice } from '@wordpress/components';
import { MediaItem } from '@wordpress/media-utils';
import { useState } from '@wordpress/element';
import { cloudUpload } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { MediaUploader } from '../';
import { File } from '../types';
import { MockMediaUpload } from './mock-media-uploader';

const ImageGallery = ( { images }: { images: File[] } ) => {
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
	return new Promise< MediaItem >( ( resolve ) => {
		const fileReader = new FileReader();
		fileReader.onload = function ( event ) {
			const image = {
				alt: 'Temporary image',
				url: event?.target?.result,
			} as MediaItem;
			resolve( image );
		};
		fileReader.readAsDataURL( file );
	} );
};

const mockUploadMedia = async ( { filesList, onFileChange } ) => {
	// The values sent by the FormFileUpload and the DropZone components are different.
	// This is why we need to transform everything into an array.
	const list = await Object.keys( filesList ).map(
		( key ) => filesList[ key ]
	);

	const images = await Promise.all(
		list.map( ( file ) => {
			if ( typeof file === 'object' ) {
				return readImage( file );
			}
			return {};
		} )
	);
	onFileChange( images );
};

export const Basic: React.FC = () => {
	const [ images, setImages ] = useState< File[] >( [] );

	return (
		<>
			<ImageGallery images={ images } />
			<MediaUploader
				MediaUploadComponent={ MockMediaUpload }
				onSelect={ ( file ) => setImages( [ ...images, file ] ) }
				onError={ () => null }
				onFileUploadChange={ ( files ) =>
					setImages( [ ...images, ...files ] )
				}
				onUpload={ ( files ) => setImages( [ ...images, ...files ] ) }
				uploadMedia={ mockUploadMedia }
			/>
		</>
	);
};

export const DisabledDropZone: React.FC = () => {
	const [ images, setImages ] = useState< File[] >( [] );

	return (
		<>
			<ImageGallery images={ images } />
			<MediaUploader
				hasDropZone={ false }
				label={ 'Click the button below to upload' }
				MediaUploadComponent={ MockMediaUpload }
				onFileUploadChange={ ( files ) =>
					setImages( [ ...images, ...files ] )
				}
				onSelect={ ( file ) => setImages( [ ...images, file ] ) }
				onError={ () => null }
				uploadMedia={ mockUploadMedia }
			/>
		</>
	);
};

export const MaxUploadFileSize: React.FC = () => {
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

export const ButtonWithOnlyIcon: React.FC = () => {
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
	title: 'WooCommerce Admin/components/MediaUploader',
	component: Basic,
};
