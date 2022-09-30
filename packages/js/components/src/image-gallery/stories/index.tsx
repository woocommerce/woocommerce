/**
 * External dependencies
 */
import { createElement, useState, Fragment } from '@wordpress/element';
import React from 'react';
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ImageGallery, ImageGalleryItem } from '../';

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

export const Basic: React.FC = () => {
	return (
		<ImageGallery MediaUploadComponent={ MockMediaUpload }>
			<ImageGalleryItem
				alt="Random image 1"
				src="https://picsum.photos/id/137/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 2"
				src="https://picsum.photos/id/208/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 3"
				src="https://picsum.photos/id/24/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 4"
				src="https://picsum.photos/id/58/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 5"
				src="https://picsum.photos/id/309/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 6"
				src="https://picsum.photos/id/46/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 7"
				src="https://picsum.photos/id/8/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 8"
				src="https://picsum.photos/id/101/200/200"
			/>
		</ImageGallery>
	);
};

export const Cover: React.FC = () => {
	return (
		<ImageGallery MediaUploadComponent={ MockMediaUpload }>
			<ImageGalleryItem
				alt="Random image 1"
				src="https://picsum.photos/id/137/200/200"
				isCover
			/>
			<ImageGalleryItem
				alt="Random image 2"
				src="https://picsum.photos/id/208/200/200"
			/>
		</ImageGallery>
	);
};

export const Columns: React.FC = () => {
	return (
		<ImageGallery columns={ 3 } MediaUploadComponent={ MockMediaUpload }>
			<ImageGalleryItem
				alt="Random image 1"
				src="https://picsum.photos/id/137/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 2"
				src="https://picsum.photos/id/208/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 3"
				src="https://picsum.photos/id/24/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 4"
				src="https://picsum.photos/id/58/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 5"
				src="https://picsum.photos/id/309/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 6"
				src="https://picsum.photos/id/46/200/200"
			/>
		</ImageGallery>
	);
};

export default {
	title: 'WooCommerce Admin/components/ImageGallery',
	component: ImageGallery,
};
