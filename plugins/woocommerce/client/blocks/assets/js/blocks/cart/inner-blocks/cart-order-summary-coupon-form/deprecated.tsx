/**
 * External dependencies
 */
import {
	createBlock,
	type BlockInstance,
	type BlockDeprecation,
} from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

const deprecated: BlockDeprecation< Record< string, unknown > >[] = [
	{
		attributes: {
			className: { type: 'string', default: '' },
			lock: {
				type: 'object',
				default: { remove: false, move: false },
			},
		},
		supports: {
			align: false,
			html: false,
			multiple: false,
			reusable: false,
		},
		save: () => <div { ...useBlockProps.save() } />,
		isEligible: ( _attributes: unknown, innerBlocks: BlockInstance[] ) =>
			innerBlocks.length === 0,
		migrate: ( attributes: Record< string, unknown > ) => [
			attributes,
			[
				createBlock(
					'woocommerce/cart-order-summary-coupon-form-input-block'
				),
				createBlock(
					'woocommerce/cart-order-summary-coupon-form-button-block'
				),
			],
		],
	},
];

export default deprecated;
