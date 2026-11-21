/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { editSetting, getSettingValue } from '../settings';
import type { OfflineGatewaySettings } from '../types';

/**
 * Build the "Accept for virtual orders" field of a gateway.
 *
 * @param methodName Lowercase payment method name, such as "cash on delivery".
 */
export function getField(
	methodName: string
): Field< OfflineGatewaySettings > {
	return {
		id: 'enable_for_virtual',
		type: 'boolean',
		Edit: 'checkbox',
		label: __( 'Accept for virtual orders', 'woocommerce' ),
		description: sprintf(
			/* translators: %s: payment method name, such as "cash on delivery". */
			__( 'Accept %s if the order is virtual', 'woocommerce' ),
			methodName
		),
		// Like the gateway, treat a missing setting as its "yes" default.
		getValue: ( { item } ) =>
			( getSettingValue( item.settings.enable_for_virtual ) ?? 'yes' ) ===
			'yes',
		setValue: ( { item, value } ) =>
			editSetting(
				item.settings,
				'enable_for_virtual',
				value ? 'yes' : 'no'
			),
	};
}
