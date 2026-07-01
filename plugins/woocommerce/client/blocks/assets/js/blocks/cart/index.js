/**
 * External dependencies
 */
import clsx from 'clsx';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType, createBlock } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import './style.scss';
import { blockName, blockAttributes } from './attributes';
import './inner-blocks';
import { metadata } from './metadata';
import { lazyEdit } from '../shared/lazy-edit';

const Edit = lazyEdit( () =>
	import( /* webpackChunkName: "cart-edit" */ './edit' ).then(
		( { Edit: CartEdit } ) => ( {
			default: CartEdit,
		} )
	)
);

const Save = () => {
	return (
		<div
			{ ...useBlockProps.save( {
				className: 'is-loading',
			} ) }
		>
			<InnerBlocks.Content />
		</div>
	);
};

/**
 * Register and run the Cart block.
 */
export const settings = {
	...metadata,
	attributes: blockAttributes,
	edit: Edit,
	save: Save,
	transforms: {
		to: [
			{
				type: 'block',
				blocks: [ 'woocommerce/classic-shortcode' ],
				transform: ( attributes ) => {
					return createBlock(
						'woocommerce/classic-shortcode',
						{
							shortcode: 'cart',
							align: attributes.align,
						},
						[]
					);
				},
			},
		],
	},
	// Migrates v1 to v2 checkout.
	deprecated: [
		{
			attributes: blockAttributes,
			save: ( { attributes } ) => {
				return (
					<div
						className={ clsx( 'is-loading', attributes.className ) }
					>
						<InnerBlocks.Content />
					</div>
				);
			},
			migrate: ( attributes, innerBlocks ) => {
				const { checkoutPageId, align } = attributes;
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
											{}
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

registerBlockType( blockName, settings );
