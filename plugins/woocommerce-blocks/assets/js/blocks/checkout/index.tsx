/**
 * External dependencies
 */
import classnames from 'classnames';
import { fields } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType, createBlock } from '@wordpress/blocks';
import type { BlockInstance } from '@wordpress/blocks';
import Immutable from 'immutable';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { blockAttributes, deprecatedAttributes } from './attributes';
import './inner-blocks';
import metadata from './block.json';

// Create a Map
const map1 = Immutable.Map( { a: 1, b: 2, c: 3 } );
console.log( 'Map1:', map1 );

// Create a List
const list1 = Immutable.List( [ 1, 2, 3, 4 ] );
console.log( 'List1:', list1 );

// Create a Set
const set1 = Immutable.Set( [ 1, 2, 3, 4, 5 ] );
console.log( 'Set1:', set1 );

// Create a Stack
const stack1 = Immutable.Stack( [ 1, 2, 3, 4, 5 ] );
console.log( 'Stack1:', stack1 );

// Create a Record
const RecordType = Immutable.Record( { name: '', age: 0 } );
const record1 = new RecordType( { name: 'Alice', age: 30 } );
console.log( 'Record1:', record1 );

const settings = {
	icon: {
		src: (
			<Icon
				icon={ fields }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
		...blockAttributes,
		...deprecatedAttributes,
	},
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
							shortcode: 'checkout',
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
			attributes: {
				...metadata.attributes,
				...blockAttributes,
				...deprecatedAttributes,
			},
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
								createBlock(
									'woocommerce/checkout-additional-information-block',
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
		// Adds the additional information block.
		{
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
			isEligible: (
				_attributes: Record< string, unknown >,
				innerBlocks: BlockInstance[]
			) => {
				const checkoutFieldsBlock = innerBlocks.find(
					( block: { name: string } ) =>
						block.name === 'woocommerce/checkout-fields-block'
				);

				if ( ! checkoutFieldsBlock ) {
					return false;
				}

				// Top level block is the fields block, we then need to search within that for the additional information block.
				return ! checkoutFieldsBlock.innerBlocks.some(
					( block: { name: string } ) =>
						block.name ===
						'woocommerce/checkout-additional-information-block'
				);
			},
			migrate: (
				attributes: Record< string, unknown >,
				innerBlocks: BlockInstance[]
			) => {
				const checkoutFieldsBlockIndex = innerBlocks.findIndex(
					( block: { name: string } ) =>
						block.name === 'woocommerce/checkout-fields-block'
				);

				if ( checkoutFieldsBlockIndex === -1 ) {
					return false;
				}

				const checkoutFieldsBlock =
					innerBlocks[ checkoutFieldsBlockIndex ];

				const insertIndex = checkoutFieldsBlock.innerBlocks.findIndex(
					( block: { name: string } ) =>
						block.name ===
						'wp-block-woocommerce-checkout-payment-block'
				);

				if ( insertIndex === -1 ) {
					return false;
				}

				innerBlocks[ checkoutFieldsBlockIndex ] =
					checkoutFieldsBlock.innerBlocks
						.slice( 0, insertIndex )
						.concat(
							createBlock(
								'woocommerce/checkout-additional-information-block',
								{},
								[]
							)
						)
						.concat(
							innerBlocks.slice(
								insertIndex + 1,
								innerBlocks.length
							)
						);

				return [ attributes, innerBlocks ];
			},
		},
	],
};

registerBlockType( metadata, settings );
