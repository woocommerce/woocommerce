/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import TextInput from '@woocommerce/base-components/text-input';
import { ShippingCountryInput } from '@woocommerce/base-components/country-input';
import { ShippingStateInput } from '@woocommerce/base-components/state-input';
import {
	COUNTRY_LOCALE,
	DEFAULT_ADDRESS_FIELDS,
} from '@woocommerce/block-settings';

const defaultFieldValues = {
	first_name: {
		autocomplete: 'given-name',
	},
	last_name: {
		autocomplete: 'family-name',
	},
	company: {
		autocomplete: 'organization',
	},
	address_1: {
		autocomplete: 'address-line1',
	},
	address_2: {
		autocomplete: 'address-line2',
	},
	country: {
		autocomplete: 'country',
		priority: 65,
	},
	city: {
		autocomplete: 'address-level2',
	},
	postcode: {
		autocomplete: 'postal-code',
	},
	state: {
		autocomplete: 'address-level1',
	},
};

const AddressForm = ( {
	fields = [
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'country',
		'city',
		'postcode',
		'state',
	],
	onChange,
	values,
} ) => {
	const countryLocale = COUNTRY_LOCALE[ values.country ] || {};
	const addressFields = fields.map( ( field ) => ( {
		key: field,
		...DEFAULT_ADDRESS_FIELDS[ field ],
		...countryLocale[ field ],
		...defaultFieldValues[ field ],
	} ) );
	const sortedAddressFields = addressFields.sort(
		( a, b ) => a.priority - b.priority
	);

	return (
		<div className="wc-block-address-form">
			{ sortedAddressFields.map( ( addressField ) => {
				if ( addressField.hidden ) {
					return null;
				}
				if ( addressField.key === 'country' ) {
					return (
						<ShippingCountryInput
							key={ addressField.key }
							label={ __(
								'Country / Region',
								'woo-gutenberg-products-block'
							) }
							value={ values.country }
							autoComplete={ addressField.autocomplete }
							onChange={ ( newValue ) =>
								onChange( {
									...values,
									country: newValue,
									state: '',
								} )
							}
							required={ true }
						/>
					);
				}
				if ( addressField.key === 'state' ) {
					return (
						<ShippingStateInput
							key={ addressField.key }
							country={ values.country }
							label={ addressField.label }
							value={ values.state }
							autoComplete={ addressField.autocomplete }
							onChange={ ( newValue ) =>
								onChange( {
									...values,
									state: newValue,
								} )
							}
							required={
								! values.country || addressField.required
							}
						/>
					);
				}
				return (
					<TextInput
						key={ addressField.key }
						className={ `wc-block-address-form__${ addressField.key }` }
						label={ addressField.label || addressField.placeholder }
						value={ values[ addressField.key ] }
						autoComplete={ addressField.autocomplete }
						onChange={ ( newValue ) =>
							onChange( {
								...values,
								[ addressField.key ]: newValue,
							} )
						}
						required={ addressField.required }
					/>
				);
			} ) }
		</div>
	);
};

AddressForm.propTypes = {
	onChange: PropTypes.func.isRequired,
	values: PropTypes.object.isRequired,
	fields: PropTypes.arrayOf( PropTypes.string ),
};

export default AddressForm;
