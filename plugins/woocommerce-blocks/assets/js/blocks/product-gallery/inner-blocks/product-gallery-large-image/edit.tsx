/**
 * External dependencies
 */
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { memo } from '@wordpress/element';

const getInnerBlocksTemplate = () => {
	return [ [ 'woocommerce/product-gallery-large-image-next-previous' ] ];
};

const Placeholder = memo( () => {
	return (
		<div className="wc-block-editor-product-gallery-large-image">
			<img
				src={ `${ WC_BLOCKS_IMAGE_URL }block-placeholders/product-image-gallery.svg` }
				alt="Placeholder"
			/>
		</div>
	);
} );

/**
 * Internal dependencies
 */
import './editor.scss';

export const Edit = (): JSX.Element => {
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
		className: 'wc-block-editor-product-gallery_large-image',
	} );

	return (
		<div { ...blockProps }>
			<Placeholder />
			<div { ...innerBlocksProps } />
		</div>
	);
};
