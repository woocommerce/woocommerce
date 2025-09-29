/**
 * External dependencies
 */
import { useSchemaParser } from '@woocommerce/base-hooks';
import {
	CURRENT_USER_IS_ADMIN,
	FormFields,
	FormType,
	KeyedParsedFormFields,
} from '@woocommerce/settings';
import { useRef } from '@wordpress/element';
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import prepareFormFields from './prepare-form-fields';
import { hasSchemaRules } from './utils';

/**
 * Combines address fields, including fields from the locale, and sorts them by index.
 */
export const useFormFields = < T extends keyof FormFields >(
	// List of field keys to include in the form.
	fieldKeys: T[],
	// Default fields from settings.
	defaultFields: FormFields,
	// Form type, can be billing, shipping, contact, order.
	formType: FormType,
	// Address country.
	addressCountry = ''
): KeyedParsedFormFields => {
	const currentResults = useRef< KeyedParsedFormFields >( [] );
	const { parser, data } = useSchemaParser( formType );

	const formFields = prepareFormFields(
		fieldKeys,
		defaultFields,
		addressCountry
	);

	const updatedFields = formFields.map( ( field ) => {
		const defaultConfig = defaultFields[ field.key ] || {};

		if ( parser ) {
			if ( hasSchemaRules( defaultConfig, 'required' ) ) {
				let schema = {};
				if (
					Object.keys( defaultConfig.required ).some(
						( key ) =>
							key === 'cart' ||
							key === 'checkout' ||
							key === 'customer'
					)
				) {
					schema = {
						type: 'object',
						properties: defaultConfig.required,
					};
				} else {
					schema = defaultConfig.required;
				}

				try {
					const result = parser.validate( schema, data );
					field.required = result;
				} catch ( error ) {
					if ( CURRENT_USER_IS_ADMIN ) {
						// eslint-disable-next-line no-console
						console.error( error );
					}
				}
			}
			if ( hasSchemaRules( defaultConfig, 'hidden' ) ) {
				let schema = {};
				if (
					Object.keys( defaultConfig.hidden ).some(
						( key ) =>
							key === 'cart' ||
							key === 'checkout' ||
							key === 'customer'
					)
				) {
					schema = {
						type: 'object',
						properties: defaultConfig.hidden,
					};
				} else {
					schema = defaultConfig.hidden;
				}

				try {
					const result = parser.validate( schema, data );
					field.hidden = result;
				} catch ( error ) {
					if ( CURRENT_USER_IS_ADMIN ) {
						// eslint-disable-next-line no-console
						console.error( error );
					}
				}
			}
		}
		return field;
	} );

	if (
		! currentResults.current ||
		! fastDeepEqual( currentResults.current, updatedFields )
	) {
		// Default required and hidden to their boolean values if they exist
		const sanitizedFields = updatedFields.map( ( field ) => ( {
			...field,
			hidden: typeof field.hidden === 'boolean' ? field.hidden : false,
			required:
				typeof field.required === 'boolean' ? field.required : false,
		} ) );

		currentResults.current = sanitizedFields;
	}

	return currentResults.current;
};
