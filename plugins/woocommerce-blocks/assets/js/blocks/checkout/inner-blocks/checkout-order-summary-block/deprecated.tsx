/**
 * External dependencies
 */
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
						'woocommerce/checkout-order-summary-cart-items-block',
						{},
						[]
					),
					createBlock(
						'woocommerce/checkout-order-summary-coupon-form-block',
						{},
						[]
					),
					createBlock(
						'woocommerce/checkout-order-summary-totals-block',
						{},
						[
							createBlock(
								'woocommerce/checkout-order-summary-subtotal-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/checkout-order-summary-fee-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/checkout-order-summary-discount-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/checkout-order-summary-shipping-block',
								{},
								[]
							),
							createBlock(
								'woocommerce/checkout-order-summary-taxes-block',
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
					block.name ===
					'woocommerce/checkout-order-summary-totals-block'
			);
		},
	},
];

export default deprecated;
