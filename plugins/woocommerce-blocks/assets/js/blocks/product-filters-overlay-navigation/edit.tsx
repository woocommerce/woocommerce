/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './types';

export const Edit = ( {}: BlockEditProps< BlockAttributes > ) => {
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filters-overlay-navigation' ),
	} );

	return (
		<div { ...blockProps }>
			{ __( 'Navigation – hello from the editor!', 'woocommerce' ) }
		</div>
	);
};
