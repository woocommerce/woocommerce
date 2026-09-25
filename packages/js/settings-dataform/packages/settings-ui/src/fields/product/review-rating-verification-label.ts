/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_review_rating_verification_label',
	label: __(
		'Show "verified owner" label on customer reviews',
		'woocommerce'
	),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
