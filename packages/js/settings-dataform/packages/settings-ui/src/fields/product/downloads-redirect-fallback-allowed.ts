/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_downloads_redirect_fallback_allowed',
	label: __(
		'Allow using redirect mode (insecure) as a last resort',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
