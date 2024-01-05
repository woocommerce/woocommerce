/**
 * External dependencies
 */
import {
	FormField,
	FormFields,
	CountryAddressForm,
	defaultFields,
	KeyedFormField,
	LocaleSpecificFormField,
	FormFieldsConfig,
} from '@woocommerce/settings';
import { __, sprintf } from '@wordpress/i18n';
import { isNumber, isString } from '@woocommerce/types';
import { COUNTRY_LOCALE } from '@woocommerce/block-settings';

/**
 * Gets props from the core locale, then maps them to the shape we require in the client.
 *
 * Ignores "class", "type", "placeholder", and "autocomplete" props from core.
 *
 * @param {Object} localeField Locale fields from WooCommerce.
 * @return {Object} Supported locale fields.
 */
const getSupportedCoreLocaleProps = (
	localeField: LocaleSpecificFormField
): Partial< FormField > => {
	const fields: Partial< FormField > = {};

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

	if ( localeField.priority ) {
		if ( isNumber( localeField.priority ) ) {
			fields.index = localeField.priority;
		}
		if ( isString( localeField.priority ) ) {
			fields.index = parseInt( localeField.priority, 10 );
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
const countryAddressForm: CountryAddressForm = Object.entries( COUNTRY_LOCALE )
	.map( ( [ country, countryLocale ] ) => [
		country,
		Object.entries( countryLocale )
			.map( ( [ localeFieldKey, localeField ] ) => [
				localeFieldKey,
				getSupportedCoreLocaleProps( localeField ),
			] )
			.reduce( ( obj, [ key, val ] ) => {
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore - Ignoring because it should be fine as long as the data from the server is correct. TS won't catch it anyway if it's not.
				obj[ key ] = val;
				return obj;
			}, {} ),
	] )
	.reduce( ( obj, [ key, val ] ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore - Ignoring because it should be fine as long as the data from the server is correct. TS won't catch it anyway if it's not.
		obj[ key ] = val;
		return obj;
	}, {} );

/**
 * Combines address fields, including fields from the locale, and sorts them by index.
 *
 * @param {Array}  fields         List of field keys--only address fields matching these will be returned.
 * @param {Object} fieldConfigs   Fields config contains field specific overrides at block level which may, for example, hide a field.
 * @param {string} addressCountry Address country code. If unknown, locale fields will not be merged.
 * @return {CountryAddressForm} Object containing address fields.
 */
const prepareFormFields = (
	fields: ( keyof FormFields )[],
	fieldConfigs: FormFieldsConfig,
	addressCountry = ''
): KeyedFormField[] => {
	const localeConfigs: FormFields =
		addressCountry && countryAddressForm[ addressCountry ] !== undefined
			? countryAddressForm[ addressCountry ]
			: ( {} as FormFields );

	return fields
		.map( ( field ) => {
			const defaultConfig = defaultFields[ field ] || {};
			const localeConfig = localeConfigs[ field ] || {};
			const fieldConfig = fieldConfigs[ field ] || {};

			return {
				key: field,
				...defaultConfig,
				...localeConfig,
				...fieldConfig,
			};
		} )
		.sort( ( a, b ) => a.index - b.index );
};

export default prepareFormFields;
