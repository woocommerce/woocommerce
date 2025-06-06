/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { heading } from '@wordpress/icons';
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockConfiguration } from '@wordpress/blocks';
import { useProductDataContext } from '@woocommerce/shared-context';
import { Spinner } from '@wordpress/components';
import { isBoolean } from '@woocommerce/types';
import { getSettingWithCoercion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const isBlockTheme = getSettingWithCoercion( 'isBlockTheme', false, isBoolean );

if ( isBlockTheme ) {
	registerBlockType( metadata.name, {
		...metadata,
		edit: function Edit() {
			const blockProps = useBlockProps();
			const { isLoading, product } = useProductDataContext();

			if ( isLoading ) {
				return <Spinner />;
			}
			return (
				<div { ...blockProps }>
					<div className="wp-block-woocommerce-add-to-cart-with-options-grouped-product-item-label">
						{ product.name }
					</div>
				</div>
			);
		},
		icon: heading,
		save: () => null,
	} as unknown as BlockConfiguration );
}
