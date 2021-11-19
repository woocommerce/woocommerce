/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Icon, fields } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';
import { BlockInstance, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { blockName, blockAttributes } from './attributes';
import './inner-blocks';

const settings = {
	title: __( 'Checkout', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ fields } />,
		foreground: '#7f54b3',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a checkout form so your customers can submit orders.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
	},
	attributes: blockAttributes,
	apiVersion: 2,
	edit: Edit,
	save: Save,
	// Migrates v1 to v2 checkout.
	deprecated: [
		{
			attributes: blockAttributes,
			save( { attributes }: { attributes: { className: string } } ) {
				return (
					<div
						className={ classnames(
							'is-loading',
							attributes.className
						) }
					/>
				);
			},
			migrate: ( attributes: {
				showOrderNotes: boolean;
				showPolicyLinks: boolean;
				showReturnToCart: boolean;
				cartPageId: number;
			} ) => {
				const {
					showOrderNotes,
					showPolicyLinks,
					showReturnToCart,
					cartPageId,
				} = attributes;
				return [
					attributes,
					[
						createBlock(
							'woocommerce/checkout-fields-block',
							{},
							[
								createBlock(
									'woocommerce/checkout-express-payment-block',
									{},
									[]
								),
								createBlock(
									'woocommerce/checkout-contact-information-block',
									{},
									[]
								),
								createBlock(
									'woocommerce/checkout-shipping-address-block',
									{},
									[]
								),
								createBlock(
									'woocommerce/checkout-billing-address-block',
									{},
									[]
								),
								createBlock(
									'woocommerce/checkout-shipping-methods-block',
									{},
									[]
								),
								createBlock(
									'woocommerce/checkout-payment-block',
									{},
									[]
								),
								showOrderNotes
									? createBlock(
											'woocommerce/checkout-order-note-block',
											{},
											[]
									  )
									: false,
								showPolicyLinks
									? createBlock(
											'woocommerce/checkout-terms-block',
											{},
											[]
									  )
									: false,
								createBlock(
									'woocommerce/checkout-actions-block',
									{
										showReturnToCart,
										cartPageId,
									},
									[]
								),
							].filter( Boolean ) as BlockInstance[]
						),
						createBlock( 'woocommerce/checkout-totals-block', {} ),
					],
				];
			},
			isEligible: (
				attributes: Record< string, unknown >,
				innerBlocks: BlockInstance[]
			) => {
				return ! innerBlocks.some(
					( block: { name: string } ) =>
						block.name === 'woocommerce/checkout-fields-block'
				);
			},
		},
	],
};

registerFeaturePluginBlockType( blockName, settings );
