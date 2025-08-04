/**
 * External dependencies
 */
import { COUNTRY_LOCALE } from '@woocommerce/block-settings';
import {
	CountryAddressFields,
	Field,
	FieldLocaleOverrides,
	FormFields,
	KeyedFormFields,
} from '@woocommerce/settings';
import { isNumber, isString } from '@woocommerce/types';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Gets props from the core locale, then maps them to the shape we require in the client.
 *
 * Ignores "class", "type", "placeholder", and "autocomplete" props from core.
 *
 * @param {Object} localeField Locale fields from WooCommerce.
 * @return {Object} Supported locale fields.
 */
const getSupportedCoreLocaleProps = (
	localeField: FieldLocaleOverrides
): Partial< Field > => {
	const fields: Partial< Field > = {};

	if ( localeField.label !== undefined ) {
		fields.label = localeField.label;
	}

	if ( localeField.required !== undefined ) {
		fields.required = localeField.required;
	}

	if ( localeField.hidden !== undefined ) {
		fields.hidden = localeField.hidden;
	}

	if ( localeField.label !== undefined && ! localeField.optionalLabel ) {
		fields.optionalLabel = sprintf(
			/* translators: %s Field label. */
			__( '%s (optional)', 'woocommerce' ),
			localeField.label
		);
	}

	if ( localeField.optionalLabel !== undefined ) {
		fields.optionalLabel = localeField.optionalLabel;
	}

	if ( localeField.index ) {
		if ( isNumber( localeField.index ) ) {
			fields.index = localeField.index;
		}
		if ( isString( localeField.index ) ) {
			fields.index = parseInt( localeField.index, 10 );
		}
	}

	if ( localeField.hidden ) {
		fields.required = false;
	}

	return fields;
};

/**
 * COUNTRY_LOCALE is locale data from WooCommerce countries class. This doesn't match the shape of the new field data blocks uses,
 * but we can import part of it to set which fields are required.
 *
 * This supports new properties such as optionalLabel which are not used by core (yet).
 */
const countryAddressFields: CountryAddressFields = Object.entries(
	COUNTRY_LOCALE
).reduce( ( acc, [ country, countryLocale ] ) => {
	acc[ country ] = Object.entries( countryLocale ).reduce(
		( fields, [ localeFieldKey, localeField ] ) => {
			fields[ localeFieldKey ] =
				getSupportedCoreLocaleProps( localeField );
			return fields;
		},
		{}
	);
	return acc;
}, {} );

/**
 * Combines address fields, including fields from the locale, and sorts them by index.
 */
const prepareFormFields = (
	// ist of field keys--only address fields matching these will be returned
	fieldKeys: ( keyof FormFields )[],
	// Default fields from settings.
	defaultFields: FormFields,
	// Address country code. If unknown, locale fields will not be merged.
	addressCountry = ''
): KeyedFormFields => {
	const localeConfigs =
		addressCountry && countryAddressFields[ addressCountry ] !== undefined
			? countryAddressFields[ addressCountry ]
			: {};

	return fieldKeys
		.map( ( field ) => {
			const defaultConfig =
				defaultFields && field in defaultFields
					? defaultFields[ field ]
					: {};
			const localeConfig =
				localeConfigs && field in localeConfigs
					? localeConfigs[ field ]
					: {};

			return {
				key: field,
				...defaultConfig,
				...localeConfig,
			};
		} )
		.sort( ( a, b ) => a.index - b.index );
};

export default prepareFormFields;
