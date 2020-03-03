/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { useState } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';

/**
 * Internal dependencies
 */
import './style.scss';
import AddressForm from '../address-form';

const ShippingCalculatorAddress = ( { address: initialAddress, onUpdate } ) => {
	const [ address, setAddress ] = useState( initialAddress );

	return (
		<form className="wc-block-shipping-calculator-address">
			<AddressForm
				fields={ [ 'country', 'state', 'city', 'postcode' ] }
				onChange={ setAddress }
				values={ address }
			/>
			<Button
				className="wc-block-shipping-calculator-address__button"
				disabled={ isShallowEqual( address, initialAddress ) }
				onClick={ () => onUpdate( address ) }
				type="submit"
			>
				{ __( 'Update', 'woo-gutenberg-products-block' ) }
			</Button>
		</form>
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
