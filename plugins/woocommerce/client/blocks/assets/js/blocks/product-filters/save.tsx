/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './editor.scss';
import { type BlockAttributes } from './types';
import { getProductFiltersCss } from './utils/get-product-filters-css';

export const Save = ( {
	attributes,
}: {
	attributes: BlockAttributes;
	style: Record< string, string >;
} ): JSX.Element => {
	const blockProps = useBlockProps.save( {
		className: clsx( 'wc-block-product-filters' ),
		style: getProductFiltersCss( attributes ),
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
