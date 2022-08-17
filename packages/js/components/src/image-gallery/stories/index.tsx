/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import React from 'react';

/**
 * Internal dependencies
 */
import { ImageGallery, ImageGalleryItem } from '../';

export const Basic: React.FC = () => {
	return (
		<ImageGallery>
			<ImageGalleryItem
				alt="Random image 1"
				src="https://picsum.photos/id/137/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 2"
				src="https://picsum.photos/id/208/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 2"
				src="https://picsum.photos/id/24/200/200"
			/>
			<ImageGalleryItem
				alt="Random image 2"
				src="https://picsum.photos/id/58/200/200"
			/>
		</ImageGallery>
	);
};

export default {
	title: 'WooCommerce Admin/components/ImageGallery',
	component: ImageGallery,
};
