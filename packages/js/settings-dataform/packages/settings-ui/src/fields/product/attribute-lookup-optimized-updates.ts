/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_attribute_lookup_optimized_updates',
	label: __(
		'Uses much more performant queries to update the lookup table, but may not be compatible with some extensions.',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
