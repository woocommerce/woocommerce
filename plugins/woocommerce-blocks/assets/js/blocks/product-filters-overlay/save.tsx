/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */

export const Save = (): JSX.Element => {
	const blockProps = useBlockProps.save( {
		className: clsx( 'wc-block-product-filters-overlay' ),
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
