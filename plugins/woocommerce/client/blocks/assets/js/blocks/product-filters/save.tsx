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
import { getOverlayMenu } from './utils/overlay-menu';

export const Save = ( {
	attributes,
}: {
	attributes: BlockAttributes;
} ): JSX.Element => {
	const overlayMenu = getOverlayMenu( attributes );
	const blockProps = useBlockProps.save( {
		className: clsx( 'wc-block-product-filters', {
			'is-filter-drawer-disabled': overlayMenu === 'never',
			'is-overlay-always': overlayMenu === 'always',
		} ),
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
