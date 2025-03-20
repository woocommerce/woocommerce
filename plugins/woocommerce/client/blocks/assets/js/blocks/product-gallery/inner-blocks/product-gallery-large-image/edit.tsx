/**
 * External dependencies
 */
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import largeImageNextPreviousButtonMetadata from '../product-gallery-large-image-next-previous/block.json';
import './editor.scss';

const getInnerBlocksTemplate = () => [
	[ largeImageNextPreviousButtonMetadata.name ],
];

const Placeholder = memo( () => {
	return (
		<div className="wc-block-product-gallery-large-image wc-block-editor-product-gallery-large-image">
			<img
				src={ `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg` }
				alt="Placeholder"
			/>
		</div>
	);
} );

export const Edit = () => {
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'wc-block-product-gallery-large-image__inner-blocks',
		},
		{
			template: getInnerBlocksTemplate(),
			templateInsertUpdatesSelection: true,
		}
	);

	const blockProps = useBlockProps( {
		className: clsx(
			'wc-block-product-gallery-large-image',
			'wc-block-editor-product-gallery-large-image'
		),
	} );

	return (
		<div { ...blockProps }>
			<Placeholder />
			<div { ...innerBlocksProps } />
		</div>
	);
};
