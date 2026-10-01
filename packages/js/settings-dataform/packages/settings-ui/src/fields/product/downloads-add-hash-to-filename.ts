/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_downloads_add_hash_to_filename',
	label: __(
		'Append a unique string to filename for security',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
