/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const translateApiErrors = ( error?: string ) => {
	switch ( error ) {
		case 'connection_error':
			return __(
				'❗ We were unable to reach the experimental service. Please check back in shortly.',
				'woocommerce'
			);
		default:
			return __(
				`❗ We encountered an issue with this experimental feature. Please check back in shortly.`,
				'woocommerce'
			);
	}
};
