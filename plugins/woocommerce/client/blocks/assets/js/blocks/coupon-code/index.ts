/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import { Save, DeprecatedSave } from './save';
import metadata from './block.json';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
registerBlockType( metadata as any, {
	title: __( 'Coupon Code', 'woocommerce' ),
	description: __(
		'Include a coupon code to entice customers to make a purchase.',
		'woocommerce'
	),
	edit: Edit,
	save: Save,
	deprecated: [
		{
			attributes: {
				couponCode: {
					type: 'string' as const,
					default: '',
				},
			},
			save: DeprecatedSave,
			migrate( attributes: Record< string, unknown > ) {
				return {
					...attributes,
					source: 'existing' as const,
				};
			},
		},
	],
} );
