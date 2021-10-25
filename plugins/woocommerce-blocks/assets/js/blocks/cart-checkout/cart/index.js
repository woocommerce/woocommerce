/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { InnerBlocks } from '@wordpress/block-editor';
import { Icon, cart } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';
import { createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import './style.scss';
import { blockName, blockAttributes } from './attributes';
import './inner-blocks';

/**
 * Register and run the Cart block.
 */
const settings = {
	title: __( 'Cart', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#7f54b3',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __( 'Shopping cart.', 'woo-gutenberg-products-block' ),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
		__experimentalExposeControlsToChildren: true,
	},
	example: {
		attributes: {
			isPreview: true,
		},
	},
	attributes: blockAttributes,
	edit: Edit,
	save: Save,
	// Migrates v1 to v2 checkout.
	deprecated: [
		{
			attributes: blockAttributes,
			save: ( { attributes } ) => {
				return (
					<div
						className={ classnames(
							'is-loading',
							attributes.className
						) }
					>
						<InnerBlocks.Content />
					</div>
				);
			},
			migrate: ( attributes, innerBlocks ) => {
				const {
					isShippingCalculatorEnabled,
					showRateAfterTaxName,
					checkoutPageId,
					align,
				} = attributes;
				return [
					attributes,
					[
						createBlock(
							'woocommerce/filled-cart-block',
							{ align },
							[
								createBlock( 'woocommerce/cart-items-block' ),
								createBlock(
									'woocommerce/cart-totals-block',
									{},
									[
										createBlock(
											'woocommerce/cart-order-summary-block',
											{
												isShippingCalculatorEnabled,
												showRateAfterTaxName,
											}
										),
										createBlock(
											'woocommerce/cart-express-payment-block'
										),
										createBlock(
											'woocommerce/proceed-to-checkout-block',
											{ checkoutPageId }
										),
										createBlock(
											'woocommerce/cart-accepted-payment-methods-block'
										),
									]
								),
							]
						),
						createBlock(
							'woocommerce/empty-cart-block',
							{ align },
							innerBlocks
						),
					],
				];
			},
			isEligible: ( _, innerBlocks ) => {
				return ! innerBlocks.find(
					( block ) => block.name === 'woocommerce/filled-cart-block'
				);
			},
		},
	],
};

registerFeaturePluginBlockType( blockName, settings );
