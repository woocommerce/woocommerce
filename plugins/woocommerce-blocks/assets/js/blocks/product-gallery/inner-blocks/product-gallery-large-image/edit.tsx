/**
 * External dependencies
 */
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import largeImageNextPreviousButtonMetadata from '../product-gallery-large-image-next-previous/block.json';
import pagerMetadata from '../product-gallery-pager/block.json';
import './editor.scss';
import { ProductGalleryContext } from '../../types';

const getInnerBlocksTemplate = () => [
	[ largeImageNextPreviousButtonMetadata.name ],
	[ pagerMetadata.name ],
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

export const Edit = ( {
	context,
}: {
	context: ProductGalleryContext;
} ): JSX.Element => {
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'wc-block-product-gallery-large-image__inner-blocks',
		},
		{
			template: getInnerBlocksTemplate(),
			templateInsertUpdatesSelection: true,
		}
	);

	const previousNextImage = useMemo( () => {
		switch ( context.nextPreviousButtonsPosition ) {
			case 'insideTheImage':
				return {
					className: 'inside-image',
				};
			case 'outsideTheImage':
				return {
					className: 'outside-image',
				};
			case 'off':
				return {
					className: 'off',
				};
			default:
				return {
					className: 'off',
				};
		}
	}, [ context.nextPreviousButtonsPosition ] );

	const blockProps = useBlockProps( {
		className: classNames(
			'wc-block-product-gallery-large-image',
			'wc-block-editor-product-gallery-large-image',
			`wc-block-editor-product-gallery-large-image-next-previous--${ previousNextImage?.className }`
		),
	} );

	return (
		<div { ...blockProps }>
			<Placeholder />
			<div { ...innerBlocksProps } />
		</div>
	);
};
