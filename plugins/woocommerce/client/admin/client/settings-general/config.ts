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

const isAddressAutocompleteProviderVisible = (
	item: Record< string, unknown >
) =>
	item.woocommerce_address_autocomplete_enabled === 'yes' ||
	item.woocommerce_address_autocomplete_enabled === true;

const isSpecificShipToCountriesVisible = (
	item: Record< string, string | string[] >
) => item.woocommerce_ship_to_countries === 'specific';

const isAllExceptCountriesVisible = (
	item: Record< string, string | string[] >
) => item.woocommerce_allowed_countries === 'all_except';

const isSpecificAllowedCountriesVisible = (
	item: Record< string, string | string[] >
) => item.woocommerce_allowed_countries === 'specific';

const getMultiselectVisibility = ( fieldId: string ) => {
	if ( fieldId === 'woocommerce_specific_ship_to_countries' ) {
		return isSpecificShipToCountriesVisible;
	}

	if ( fieldId === 'woocommerce_all_except_countries' ) {
		return isAllExceptCountriesVisible;
	}

	if ( fieldId === 'woocommerce_specific_allowed_countries' ) {
		return isSpecificAllowedCountriesVisible;
	}

	return () => true;
};

export const fieldTransformer = ( field: ReactSettingsField ) => {
	const baseField = baseFieldTransformer( field ) as Record<
		string,
		unknown
	>;

	if ( field.id === 'woocommerce_address_autocomplete_provider' ) {
		return {
			...baseField,
			isVisible: isAddressAutocompleteProviderVisible,
		};
	}

	if ( field.type === 'multiselect' ) {
		return {
			...baseField,
			isVisible: getMultiselectVisibility( field.id ),
		};
	}

	return baseField;
};
