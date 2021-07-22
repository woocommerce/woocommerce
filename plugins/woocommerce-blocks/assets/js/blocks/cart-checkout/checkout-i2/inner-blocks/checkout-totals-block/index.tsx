/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType( 'woocommerce/checkout-totals-block', {
	title: __( 'Checkout Totals Block', 'woo-gutenberg-products-block' ),
	category: 'woocommerce',
	description: __(
		'Wrapper block for checkout totals',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: false,
		html: false,
		multiple: false,
		reusable: false,
		inserter: false,
	},
	parent: [ 'woocommerce/checkout-i2' ],
	attributes: {},
	apiVersion: 2,
	edit: Edit,
	save: Save,
} );
