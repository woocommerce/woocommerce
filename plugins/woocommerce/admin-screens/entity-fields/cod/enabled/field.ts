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
	id: 'enabled',
	type: 'boolean',
	Edit: 'toggle',
	label: __( 'Enable cash on delivery payments', 'woocommerce' ),
};
