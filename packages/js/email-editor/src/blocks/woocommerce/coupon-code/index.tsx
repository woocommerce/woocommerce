/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { tag as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { Edit } from './edit';
import { Save } from './save';

/**
 * Register the Coupon Code block.
 */
export function registerCouponCodeBlock() {
	// @ts-expect-error: 'email' is a custom property not in BlockSupports type
	registerBlockType( 'woocommerce/coupon-code', {
		apiVersion: 3,
		title: 'Coupon Code',
		category: 'woocommerce',
		description: 'Display a coupon code in your email.',
		icon,
		supports: {
			email: true,
			html: false,
			align: true,
			color: {
				text: true,
				background: true,
			},
			typography: {
				fontSize: true,
				lineHeight: true,
			},
			spacing: {
				margin: true,
				padding: true,
			},
		},
		attributes: {
			couponId: {
				type: 'number',
				default: 0,
			},
			couponCode: {
				type: 'string',
				default: '',
			},
		},
		edit: Edit,
		save: Save,
	} );
}
