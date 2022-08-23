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
		<ImageGallery>
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
		<ImageGallery columns={ 3 }>
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
