/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_product_match_featured_image_by_sku',
	label: __(
		'Set product featured image when uploaded image file name matches product SKU.',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
