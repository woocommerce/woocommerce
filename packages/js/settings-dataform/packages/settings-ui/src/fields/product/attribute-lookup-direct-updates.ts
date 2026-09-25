/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_attribute_lookup_direct_updates',
	label: __(
		'Update the table directly upon product changes, instead of scheduling a deferred update.',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
