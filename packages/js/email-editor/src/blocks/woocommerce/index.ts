/**
 * External dependencies
 */
import { setCategories, getCategories } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerCouponCodeBlock } from './coupon-code';

/**
 * Register all WooCommerce blocks.
 */
export function registerWooCommerceBlocks() {
	// Register WooCommerce block category
	const existingCategories = getCategories();
	const hasWooCommerceCategory = existingCategories.some(
		( category ) => category.slug === 'woocommerce'
	);

	if ( ! hasWooCommerceCategory ) {
		setCategories( [
			...existingCategories,
			{
				slug: 'woocommerce',
				title: __( 'WooCommerce', 'woocommerce' ),
				icon: null,
			},
		] );
	}

	registerCouponCodeBlock();
}
