/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { getClassNameByNextPreviousButtonsPosition } from './utils';
import { ProductGalleryAttributes } from './types';

export const Save = ( {
	attributes,
}: {
	attributes: ProductGalleryAttributes;
} ): JSX.Element => {
	const blockProps = useBlockProps.save( {
		className: clsx(
			'wc-block-product-gallery',
			getClassNameByNextPreviousButtonsPosition(
				attributes.nextPreviousButtonsPosition
			)
		),
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
