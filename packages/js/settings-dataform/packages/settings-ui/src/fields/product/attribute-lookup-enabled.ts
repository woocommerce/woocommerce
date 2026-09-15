/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_attribute_lookup_enabled',
	label: __(
		'Use the product attributes lookup table for catalog filtering.',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
