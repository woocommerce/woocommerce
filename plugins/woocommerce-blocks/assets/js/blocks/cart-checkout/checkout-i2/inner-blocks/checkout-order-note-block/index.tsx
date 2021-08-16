/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, notes } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType( 'woocommerce/checkout-order-note-block', {
	title: __( 'Order Note', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Allow customers to add a note to their order.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ notes } />,
		foreground: '#874FB9',
	},
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
	},
	parent: [ 'woocommerce/checkout-fields-block' ],
	attributes: {
		lock: {
			type: 'object',
			default: {
				move: true,
				remove: true,
			},
		},
	},
	apiVersion: 2,
	edit: Edit,
	save: Save,
} );
