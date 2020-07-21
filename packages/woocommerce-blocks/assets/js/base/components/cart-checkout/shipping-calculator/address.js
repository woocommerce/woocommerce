/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import {
	AddressForm,
	Button,
} from '@woocommerce/base-components/cart-checkout';
import { useState } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { useValidationContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';

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

	// Make all fields optional except 'country'.
	const fieldConfig = {};
	addressFields.forEach( ( field ) => {
		if ( field === 'country' ) {
			fieldConfig[ field ] = {
				...fieldConfig[ field ],
				errorMessage: __(
					'Please select a country to calculate rates.',
					'woocommerce'
				),
				required: true,
			};
		} else {
			fieldConfig[ field ] = {
				...fieldConfig[ field ],
				required: false,
			};
		}
	} );

	return (
		<form className="wc-block-shipping-calculator-address">
			<AddressForm
				fields={ addressFields }
				fieldConfig={ fieldConfig }
				onChange={ setAddress }
				values={ address }
			/>
			<Button
				className="wc-block-shipping-calculator-address__button"
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
