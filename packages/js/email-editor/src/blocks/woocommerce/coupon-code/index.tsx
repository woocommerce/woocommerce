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
		description:
			'Include a coupon code to entice recipients to make a purchase.',
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
			},
			spacing: {
				margin: true,
				padding: true,
			},
			__experimentalBorder: {
				color: true,
				radius: true,
				style: true,
				width: true,
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
