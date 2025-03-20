/**
 * External dependencies
 */
import {
	DocumentObject,
	usePrevious,
	useSchemaParser,
} from '@woocommerce/base-hooks';
import { getFieldLabel, isPostcode } from '@woocommerce/blocks-checkout';
import {
	AddressFormValues,
	ContactFormValues,
	FormFields,
	FormType,
	KeyedFormFields,
	OrderFormValues,
} from '@woocommerce/settings';
import { nonNullable } from '@woocommerce/types';
import { useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { isEmail } from '@wordpress/url';
import type { ErrorObject, JSONSchemaType } from 'ajv';
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import { hasSchemaRules } from './utils';

type FormErrors = Partial< {
	[ key in keyof FormFields ]: string;
} >;

/**
 * Get the key of the field from the instance path.
 *
 * @param instancePath The instance path of the error.
 * @return string The key of the field.
 */
const getFieldKey = (
	instancePath: ErrorObject[ 'instancePath' ]
): keyof FormFields => {
	return instancePath
		.split( '/' )
		.pop()
		?.replace( '~1', '/' ) as keyof FormFields; // Only place where we need to use as keyof FormFields because we're transforming a string.
};

const getErrorsMap = (
	errors: ErrorObject[],
	formFields: KeyedFormFields
): FormErrors => {
	return errors.reduce< FormErrors >( ( acc, error ) => {
		const fieldKey = getFieldKey( error.instancePath );
		const formField = formFields.find(
			( field ) => field.key === fieldKey
		);
		if ( ! formField || ! fieldKey ) {
			return acc;
		}

		const fieldLabel = getFieldLabel( formField.label );
		const defaultMessage = sprintf(
			// translators: %s is the label of the field.
			__( '%s is invalid', 'woocommerce' ),
			fieldLabel
		);
		if ( fieldKey ) {
			switch ( error.keyword ) {
				case 'errorMessage':
					acc[ fieldKey ] = error.message ?? defaultMessage;
					break;
				case 'pattern':
					acc[ fieldKey ] = sprintf(
						// translators: %1$s is the label of the field, %2$s is the pattern.
						__( '%1$s must match the pattern %2$s', 'woocommerce' ),
						fieldLabel,
						error.params.pattern
					);
					break;
				default:
					acc[ fieldKey ] = defaultMessage;
					break;
			}
		}

		return acc;
	}, {} );
};

const EMPTY_OBJECT: FormErrors = {};
/**
 * Combines address fields, including fields from the locale, and sorts them by index.
 */
export const useFormValidation = (
	formFields: KeyedFormFields,
	// Form type, can be billing, shipping, contact, order, or calculator.
	formType: FormType
): {
	errors: FormErrors;
	previousErrors: FormErrors | undefined;
} => {
	const { parser, data } = useSchemaParser< typeof formType >( formType );
	const currentResults = useRef< FormErrors >( EMPTY_OBJECT );
	const previousErrors = usePrevious( currentResults.current );

	if ( ! data ) {
		return {
			errors: currentResults.current,
			previousErrors,
		};
	}

	let values:
		| AddressFormValues
		| ContactFormValues
		| OrderFormValues
		| Record< string, never >;

	switch ( formType ) {
		case 'billing':
		case 'shipping':
			values = data.customer.address || {};
			break;
		case 'contact':
		case 'order':
			values = data.checkout.additional_fields || {};
			break;
		default:
			values = {};
			break;
	}

	const partialSchema = formFields.reduce<
		Partial<
			Record<
				keyof FormFields,
				JSONSchemaType< DocumentObject< typeof formType > >
			>
		>
	>( ( acc, field ) => {
		if (
			hasSchemaRules( field, 'validation' ) && // Schema validation only run for fields with validation rules.
			! field.hidden && // And visible
			( field.required || field.key in values ) // And is required or has a optional with a value (or both).
		) {
			acc[ field.key ] = field.validation;
		}
		return acc;
	}, {} );

	let schemaErrorsMap = EMPTY_OBJECT;

	if ( Object.keys( partialSchema ).length > 0 && parser ) {
		const schema = {
			type: 'object',
			properties: {},
		};
		switch ( formType ) {
			case 'shipping':
				schema.properties = {
					customer: {
						type: 'object',
						properties: {
							shipping_address: {
								type: 'object',
								properties: partialSchema,
							},
						},
					},
				};
				break;
			case 'billing':
				schema.properties = {
					customer: {
						type: 'object',
						properties: {
							billing_address: {
								type: 'object',
								properties: partialSchema,
							},
						},
					},
				};
				break;
			default:
				schema.properties = {
					checkout: {
						type: 'object',
						properties: {
							additional_fields: {
								type: 'object',
								properties: partialSchema,
							},
						},
					},
				};
				break;
		}
		const validate = parser.compile( schema );
		const result = validate( data );

		if ( ! result && validate.errors ) {
			schemaErrorsMap = getErrorsMap( validate.errors, formFields );
		} else {
			schemaErrorsMap = EMPTY_OBJECT;
		}
	}

	const customValidation = formFields
		.map( ( field ) => {
			if ( schemaErrorsMap[ field.key ] ) {
				return [ field.key, schemaErrorsMap[ field.key ] ];
			}

			// Pass validation if the field is not required and is empty.
			if ( ! field.required && ! ( field.key in values ) ) {
				return null;
			}

			if (
				field.key === 'postcode' &&
				'country' in values &&
				! isPostcode( {
					postcode: values.postcode,
					country: values.country,
				} )
			) {
				return [
					field.key,
					__( 'Please enter a valid postcode', 'woocommerce' ),
				];
			}

			if (
				field.key === 'email' &&
				'email' in values &&
				! isEmail( values.email )
			) {
				return [
					field.key,
					__( 'Please enter a valid email address', 'woocommerce' ),
				];
			}

			return null;
		} )
		.filter( nonNullable );

	if (
		! fastDeepEqual(
			currentResults.current,
			Object.fromEntries( customValidation )
		)
	) {
		currentResults.current = Object.fromEntries( customValidation );
	}

	return {
		errors: currentResults.current,
		previousErrors,
	};
};
