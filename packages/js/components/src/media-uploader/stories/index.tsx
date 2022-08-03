/**
 * External dependencies
 */
import React, { createElement } from 'react';
import { MediaItem } from '@wordpress/media-utils';
import { Modal, Notice } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MediaUploader } from '../';
import { ErrorType, File } from '../types';

const MockMediaUpload = ( { onSelect, render } ) => {
	const [ isOpen, setOpen ] = useState( false );

	return (
		<>
			{ render( {
				open: () => setOpen( true ),
			} ) }
			{ isOpen && (
				<Modal
					title="Media Modal"
					onRequestClose={ () => setOpen( false ) }
				>
					<p>
						Use the default built-in{ ' ' }
						<code>MediaUploadComponent</code> prop to render the WP
						Media Modal.
					</p>
					{ Array( ...Array( 3 ) ).map( ( n, i ) => {
						return (
							<button
								key={ i }
								onClick={ () => {
									onSelect( {
										alt: 'Random',
										url: `https://picsum.photos/200?i=${ i }`,
									} );
									setOpen( false );
								} }
								style={ {
									marginRight: '16px',
								} }
							>
								<img
									src={ `https://picsum.photos/200?i=${ i }` }
									alt="Random"
									style={ {
										maxWidth: '100px',
									} }
								/>
							</button>
						);
					} ) }
				</Modal>
			) }
		</>
	);
};

const ImageGallery = ( { images }: { images: File[] } ) => {
	return (
		<>
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
		</>
	);
};

const mockUploadMedia = ( { filesList, onFileChange } ) =>
	new Promise< void >( ( resolve ) => {
		const fileReader = new FileReader();
		fileReader.onload = function ( event ) {
			const file = {
				alt: 'Temporary image',
				url: event?.target?.result,
			} as MediaItem;
			onFileChange( [ file ] );
			resolve();
		};
		fileReader.readAsDataURL( filesList[ 0 ] );
	} );

export const Basic: React.FC = () => {
	const [ images, setImages ] = useState< File[] >( [] );

	return (
		<>
			<ImageGallery images={ images } />
			<MediaUploader
				MediaUploadComponent={ MockMediaUpload }
				onSelect={ ( file ) => setImages( [ ...images, file ] ) }
				onError={ () => null }
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
				MediaUploadComponent={ MockMediaUpload }
				onSelect={ ( file ) => setImages( [ ...images, file ] ) }
				onError={ () => null }
				uploadMedia={ mockUploadMedia }
			/>
		</>
	);
};

export const MaxUploadFileSize: React.FC = () => {
	const [ error, setError ] = useState< ErrorType | null >( null );

	return (
		<>
			{ error && <Notice status={ 'error' }>{ error.message }</Notice> }

			<MediaUploader
				maxUploadFileSize={ 1000 }
				MediaUploadComponent={ MockMediaUpload }
				onSelect={ () => null }
				onError={ ( e ) => setError( e ) }
				onUpload={ () => null }
			/>
		</>
	);
};

export default {
	title: 'WooCommerce Admin/components/MediaUploader',
	component: Basic,
};
