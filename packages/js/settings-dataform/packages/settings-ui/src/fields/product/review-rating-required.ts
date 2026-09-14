/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default {
	id: 'woocommerce_review_rating_required',
	label: __( 'Star ratings should be required, not optional', 'woocommerce' ),
	type: 'boolean' as const,
	Edit: 'checkbox',
};
