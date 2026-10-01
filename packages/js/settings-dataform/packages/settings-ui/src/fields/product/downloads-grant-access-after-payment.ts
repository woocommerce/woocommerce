/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_downloads_grant_access_after_payment',
	label: __(
		'Grant access to downloadable products after payment',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
