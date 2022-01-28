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
		defaultTitle: __( 'Billing address', 'woo-gutenberg-products-block' ),
		defaultDescription: __(
			'Enter the address that matches your card or payment method.',
			'woo-gutenberg-products-block'
		),
	} ),
	className: {
		type: 'string',
		default: '',
	},
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
};
