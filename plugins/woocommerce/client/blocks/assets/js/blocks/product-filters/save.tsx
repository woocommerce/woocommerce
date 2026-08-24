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

export const Save = ( {
	attributes,
}: {
	attributes: BlockAttributes;
} ): JSX.Element => {
	const hasOverlay =
		attributes.overlayOnDesktop === true ||
		attributes.showFilterDrawer !== false;
	const blockProps = useBlockProps.save( {
		className: clsx( 'wc-block-product-filters', {
			'is-filter-drawer-disabled': ! hasOverlay,
		} ),
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
