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
		defaultTitle: __( 'Shipping options', 'woo-gutenberg-products-block' ),
		defaultDescription: __(
			'Select shipping options below.',
			'woo-gutenberg-products-block'
		),
	} ),
	allowCreateAccount: {
		type: 'boolean',
		default: false,
	},
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
};
