/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { ShippingCountryInput } from '@woocommerce/base-components/country-input';
import { ShippingStateInput } from '@woocommerce/base-components/state-input';
import TextInput from '@woocommerce/base-components/text-input';
import { useState } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import './style.scss';

const ShippingCalculatorAddress = ( { address: initialAddress, onUpdate } ) => {
	const [ address, setAddress ] = useState( initialAddress );

	return (
		<div className="wc-block-shipping-calculator-address">
			<ShippingCountryInput
				className="wc-block-shipping-calculator-address__input"
				label={ __(
					'Country / Region',
					'woo-gutenberg-products-block'
				) }
				value={ address.country }
				onChange={ ( newValue ) =>
					setAddress( {
						...address,
						country: newValue,
						state: '',
					} )
				}
			/>
			<ShippingStateInput
				className="wc-block-shipping-calculator-address__input"
				country={ address.country }
				label={ __( 'State / County', 'woo-gutenberg-products-block' ) }
				value={ address.state }
				onChange={ ( newValue ) =>
					setAddress( {
						...address,
						state: newValue,
					} )
				}
			/>
			<TextInput
				className="wc-block-shipping-calculator-address__input"
				label={ __( 'City', 'woo-gutenberg-products-block' ) }
				value={ address.city }
				onChange={ ( newValue ) =>
					setAddress( {
						...address,
						city: newValue,
					} )
				}
			/>
			<TextInput
				className="wc-block-shipping-calculator-address__input"
				label={ __( 'Postal code', 'woo-gutenberg-products-block' ) }
				value={ address.postcode }
				onChange={ ( newValue ) =>
					setAddress( {
						...address,
						postcode: newValue,
					} )
				}
			/>
			<Button
				className="wc-block-shipping-calculator-address__button"
				disabled={ isShallowEqual( address, initialAddress ) }
				onClick={ () => onUpdate( address ) }
			>
				{ __( 'Update', 'woo-gutenberg-products-block' ) }
			</Button>
		</div>
	);
};

ShippingCalculatorAddress.propTypes = {
	address: PropTypes.shape( {
		city: PropTypes.string,
		state: PropTypes.string,
		postcode: PropTypes.string,
		country: PropTypes.string,
	} ),
	onUpdate: PropTypes.func.isRequired,
};

export default ShippingCalculatorAddress;
