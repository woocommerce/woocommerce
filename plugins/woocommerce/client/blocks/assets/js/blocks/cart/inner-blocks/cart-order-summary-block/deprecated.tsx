/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const deprecated = [
	{
		attributes: metadata.attributes,
		save: () => {
			return (
				<div { ...useBlockProps.save() }>
					<InnerBlocks.Content />
				</div>
			);
		},
		supports: metadata.supports,
		migrate: ( { attributes } ) => {
			return [
				attributes,
				[
					createBlock(
						'woocommerce/cart-order-summary-heading-block',
						{
							content: __( 'Cart totals', 'woocommerce' ),
						},
						[]
					),
					createBlock(
						'woocommerce/cart-order-summary-coupon-form-block',
						{},
						[]
					),
					createBlock(
						'woocommerce/cart-order-summary-totals-block',
						{},
						[
							createBlock(
								'woocommerce/cart-order-summary-subtotal-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/cart-order-summary-fee-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/cart-order-summary-discount-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/cart-order-summary-shipping-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/cart-order-summary-taxes-block',
								{},
								[]
							),
						]
					),
				],
			];
		},
		isEligible: ( attributes, innerBlocks ) => {
			return ! innerBlocks.some(
				( block ) =>
					block.name === 'woocommerce/cart-order-summary-totals-block'
			);
		},
	},
];

export default deprecated;
