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
		defaultTitle: __( 'Contact information', 'woocommerce' ),
		defaultDescription: '',
	} ),
	className: {
		type: 'string',
		default: '',
	},
	lock: {
		type: 'object',
		default: {
			remove: true,
			move: true,
		},
	},
};
