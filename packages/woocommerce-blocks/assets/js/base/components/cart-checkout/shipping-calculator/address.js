/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { useState } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { useValidationContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';
import { AddressForm } from '../address-form';

const ShippingCalculatorAddress = ( {
	address: initialAddress,
	onUpdate,
	addressFields,
} ) => {
	const [ address, setAddress ] = useState( initialAddress );
	const {
		hasValidationErrors,
		showAllValidationErrors,
	} = useValidationContext();

	const validateSubmit = () => {
		showAllValidationErrors();
		return ! hasValidationErrors;
	};

	return (
		<form className="wc-block-components-shipping-calculator-address">
			<AddressForm
				fields={ addressFields }
				onChange={ setAddress }
				values={ address }
			/>
			<Button
				className="wc-block-components-shipping-calculator-address__button"
				disabled={ isShallowEqual( address, initialAddress ) }
				onClick={ ( e ) => {
					e.preventDefault();
					const isAddressValid = validateSubmit();
					if ( isAddressValid ) {
						return onUpdate( address );
					}
				} }
				type="submit"
			>
				{ __( 'Update', 'woocommerce' ) }
			</Button>
		</form>
	);
};

ShippingCalculatorAddress.propTypes = {
	address: PropTypes.object.isRequired,
	onUpdate: PropTypes.func.isRequired,
	addressFields: PropTypes.array.isRequired,
};

export default ShippingCalculatorAddress;
