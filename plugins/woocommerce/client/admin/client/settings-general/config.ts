/**
 * Internal dependencies
 */
import { baseFieldTransformer } from '../settings/field-transformers';
import type { ReactSettingsField, RowConfigurations } from '../settings/types';

/**
 * Row configuration for grouping fields into rows.
 */
export const rowConfigurations: RowConfigurations = {
	store_address: [
		{
			id: 'city_zipcode_row',
			fields: [ 'woocommerce_store_city', 'woocommerce_store_postcode' ],
		},
	],
	pricing_options: [
		{
			id: 'separators_row',
			fields: [
				'woocommerce_price_thousand_sep',
				'woocommerce_price_decimal_sep',
			],
		},
		{
			id: 'position_decimals_row',
			fields: [
				'woocommerce_currency_pos',
				'woocommerce_price_num_decimals',
			],
		},
	],
	time_date_format_settings: [
		{
			id: 'datetime_formats_row',
			fields: [ 'date_format', 'time_format' ],
		},
	],
};

export const fieldTransformer = ( field: ReactSettingsField ) => {
	const baseField = baseFieldTransformer( field ) as Record<
		string,
		unknown
	>;

	if ( field.id === 'woocommerce_address_autocomplete_provider' ) {
		return {
			...baseField,
			isVisible: ( item: Record< string, unknown > ) =>
				item.woocommerce_address_autocomplete_enabled === 'yes' ||
				item.woocommerce_address_autocomplete_enabled === true,
		};
	}

	if ( field.type === 'multiselect' ) {
		return {
			...baseField,
			isVisible: ( item: Record< string, string | string[] > ) => {
				if ( field.id === 'woocommerce_specific_ship_to_countries' ) {
					return item.woocommerce_ship_to_countries === 'specific';
				}

				if ( field.id === 'woocommerce_all_except_countries' ) {
					return item.woocommerce_allowed_countries === 'all_except';
				}

				if ( field.id === 'woocommerce_specific_allowed_countries' ) {
					return item.woocommerce_allowed_countries === 'specific';
				}
				return true;
			},
		};
	}

	return baseField;
};
