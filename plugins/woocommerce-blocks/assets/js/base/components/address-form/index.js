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

/**
 * Internal dependencies
 */
import defaultAddressFields from './default-address-fields';
import countryAddressFields from './country-address-fields';

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
