/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import TextInput from '@woocommerce/base-components/text-input';
import {
	BillingCountryInput,
	ShippingCountryInput,
} from '@woocommerce/base-components/country-input';
import {
	BillingStateInput,
	ShippingStateInput,
} from '@woocommerce/base-components/state-input';
import { useValidationContext } from '@woocommerce/base-context';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import defaultAddressFields from './default-address-fields';
import countryAddressFields from './country-address-fields';

const validateCountry = (
	values,
	setValidationErrors,
	clearValidationError,
	hasValidationError
) => {
	if (
		! hasValidationError &&
		! values.country &&
		Object.values( values ).some( ( value ) => value !== '' )
	) {
		setValidationErrors( {
			country: __(
				'Please select a country to calculate rates.',
				'woo-gutenberg-products-block'
			),
		} );
	}
	if ( hasValidationError && values.country ) {
		clearValidationError( 'country' );
	}
};

/**
 * Checkout address form.
 */
const AddressForm = ( {
	fields = Object.keys( defaultAddressFields ),
	fieldConfig = {},
	onChange,
	type = 'shipping',
	values,
} ) => {
	const {
		getValidationError,
		setValidationErrors,
		clearValidationError,
	} = useValidationContext();
	const countryLocale = countryAddressFields[ values.country ] || {};
	const addressFields = fields.map( ( field ) => ( {
		key: field,
		...defaultAddressFields[ field ],
		...fieldConfig[ field ],
		...countryLocale[ field ],
	} ) );
	const sortedAddressFields = addressFields.sort(
		( a, b ) => a.index - b.index
	);
	const countryValidationError = getValidationError( 'country' );
	useEffect( () => {
		validateCountry(
			values,
			setValidationErrors,
			clearValidationError,
			!! countryValidationError
		);
	}, [
		values,
		countryValidationError,
		setValidationErrors,
		clearValidationError,
	] );
	return (
		<div className="wc-block-address-form">
			{ sortedAddressFields.map( ( field ) => {
				if ( field.hidden ) {
					return null;
				}

				if ( field.key === 'country' ) {
					const Tag =
						type === 'shipping'
							? ShippingCountryInput
							: BillingCountryInput;
					return (
						<Tag
							key={ field.key }
							label={
								field.required
									? field.label
									: field.optionalLabel
							}
							value={ values.country }
							autoComplete={ field.autocomplete }
							onChange={ ( newValue ) =>
								onChange( {
									...values,
									country: newValue,
									state: '',
									city: '',
									postcode: '',
								} )
							}
							required={ field.required }
						/>
					);
				}

				if ( field.key === 'state' ) {
					const Tag =
						type === 'shipping'
							? ShippingStateInput
							: BillingStateInput;
					return (
						<Tag
							key={ field.key }
							country={ values.country }
							label={
								field.required
									? field.label
									: field.optionalLabel
							}
							value={ values.state }
							autoComplete={ field.autocomplete }
							onChange={ ( newValue ) =>
								onChange( {
									...values,
									state: newValue,
								} )
							}
							required={ field.required }
						/>
					);
				}

				return (
					<TextInput
						key={ field.key }
						className={ `wc-block-address-form__${ field.key }` }
						label={
							field.required ? field.label : field.optionalLabel
						}
						value={ values[ field.key ] }
						autoComplete={ field.autocomplete }
						onChange={ ( newValue ) =>
							onChange( {
								...values,
								[ field.key ]: newValue,
							} )
						}
						required={ field.required }
					/>
				);
			} ) }
		</div>
	);
};

AddressForm.propTypes = {
	onChange: PropTypes.func.isRequired,
	values: PropTypes.object.isRequired,
	fields: PropTypes.arrayOf(
		PropTypes.oneOf( Object.keys( defaultAddressFields ) )
	),
	fieldConfig: PropTypes.object,
	type: PropTypes.oneOf( [ 'billing', 'shipping' ] ),
};

export default AddressForm;
