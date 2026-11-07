/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { resolveSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { editSetting, getSettingValue } from '../settings';
import type { OfflineGatewaySettings, ShippingMethodOptions } from '../types';

/**
 * List the shipping method options as one flat list. Each label already names its method.
 */
export function getShippingMethodElements(
	options: ShippingMethodOptions
): { value: string; label: string }[] {
	return Object.values( options ).flatMap( ( methods ) =>
		Object.entries( methods ).map( ( [ value, label ] ) => ( {
			value,
			label: decodeEntities( label ),
		} ) )
	);
}

/**
 * Build the "Enable for shipping methods" field of a gateway.
 *
 * The options come with the gateway's saved record, which the page has already loaded.
 */
export function getField(
	kind: string,
	name: string
): Field< OfflineGatewaySettings > {
	return {
		id: 'enable_for_methods',
		type: 'array',
		label: __( 'Enable for shipping methods', 'woocommerce' ),
		description: __(
			'Select shipping methods for which this payment method is enabled.',
			'woocommerce'
		),
		placeholder: __( 'Select shipping methods', 'woocommerce' ),
		getElements: async () => {
			const record = ( await resolveSelect( coreStore ).getEntityRecord(
				kind,
				name,
				name
			) ) as OfflineGatewaySettings | undefined;
			const setting = record?.settings.enable_for_methods;
			return getShippingMethodElements(
				setting && ! Array.isArray( setting )
					? ( setting.options ?? {} )
					: {}
			);
		},
		getValue: ( { item } ) => {
			const value = getSettingValue( item.settings.enable_for_methods );
			return Array.isArray( value ) ? ( value as string[] ) : [];
		},
		setValue: ( { item, value } ) =>
			editSetting(
				item.settings,
				'enable_for_methods',
				Array.isArray( value ) ? value : []
			),
	};
}
