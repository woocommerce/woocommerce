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
	const overlayOnDesktop = attributes.overlayOnDesktop === true;
	const showFilterDrawer =
		overlayOnDesktop || attributes.showFilterDrawer !== false;
	const blockProps = useBlockProps.save( {
		className: clsx( 'wc-block-product-filters', {
			'is-filter-drawer-disabled': ! showFilterDrawer,
			'has-desktop-overlay': overlayOnDesktop,
			'is-desktop-overlay-right':
				overlayOnDesktop &&
				attributes.desktopOverlayPosition === 'right',
		} ),
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
