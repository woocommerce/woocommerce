/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_enable_review_rating',
	label: __( 'Enable star rating on reviews', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
