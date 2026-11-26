/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import type { OfflineGatewaySettings } from '../../offline/types';

export const field: Field< OfflineGatewaySettings > = {
	id: 'title',
	type: 'text',
	label: __( 'Title', 'woocommerce' ),
	description: __(
		'Payment method name that the customer will see during checkout.',
		'woocommerce'
	),
	placeholder: __( 'Cash on delivery payments', 'woocommerce' ),
};
