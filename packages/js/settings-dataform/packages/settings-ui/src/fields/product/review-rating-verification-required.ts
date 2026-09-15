/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_review_rating_verification_required',
	label: __( 'Reviews can only be left by "verified owners"', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
