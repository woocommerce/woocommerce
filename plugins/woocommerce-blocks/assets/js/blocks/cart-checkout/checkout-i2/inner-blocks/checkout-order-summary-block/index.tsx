/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, totals } from '@woocommerce/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';

registerFeaturePluginBlockType( 'woocommerce/checkout-order-summary-block', {
	title: __( 'Order Summary', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Show customers a summary of their order.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: <Icon srcElement={ totals } />,
		foreground: '#874FB9',
	},
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
	},
	parent: [ 'woocommerce/checkout-totals-block' ],
	attributes,
	apiVersion: 2,
	edit: Edit,
	save: Save,
} );
