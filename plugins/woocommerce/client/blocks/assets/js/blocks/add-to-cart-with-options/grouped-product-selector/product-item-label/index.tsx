/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { heading } from '@wordpress/icons';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { shouldBlockifiedAddToCartWithOptionsBeRegistered } from '../../utils';

if ( shouldBlockifiedAddToCartWithOptionsBeRegistered ) {
	registerBlockType( metadata.name, {
		...metadata,
		edit: function Edit() {
			const blockProps = useBlockProps();

			return (
				<div { ...blockProps }>
					<div className="wp-block-woocommerce-add-to-cart-with-options-grouped-product-selector-item-label">
						{ __( 'Product Title', 'woocommerce' ) }
					</div>
				</div>
			);
		},
		icon: heading,
		save: () => null,
	} as unknown as BlockConfiguration );
}
