/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import formStepAttributes from '../../form-step/attributes';

export default {
	...formStepAttributes( {
		defaultTitle: __( 'Payment Method', 'woo-gutenberg-products-block' ),
		defaultDescription: __(
			'Select a payment method below.',
			'woo-gutenberg-products-block'
		),
	} ),
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
};
