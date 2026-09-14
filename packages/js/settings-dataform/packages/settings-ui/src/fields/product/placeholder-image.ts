/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_placeholder_image',
	label: __( 'Placeholder image', 'woocommerce' ),
	type: 'text' as const,
	description: __(
		'This is the attachment ID, or image URL, used for placeholder images in the product catalog. Products with no image will use this.',
		'woocommerce'
	),
};
