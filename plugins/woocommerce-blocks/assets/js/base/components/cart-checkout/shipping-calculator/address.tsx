/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { useState } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import type { ShippingAddress, AddressFields } from '@woocommerce/settings';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.scss';
import { AddressForm } from '../address-form';

interface ShippingCalculatorAddressProps {
	address: ShippingAddress;
	onUpdate: ( address: ShippingAddress ) => void;
	addressFields: Partial< keyof AddressFields >[];
}
const ShippingCalculatorAddress = ( {
	address: initialAddress,
	onUpdate,
	addressFields,
}: ShippingCalculatorAddressProps ): JSX.Element => {
	const [ address, setAddress ] = useState( initialAddress );
	const { showAllValidationErrors } = useDispatch( VALIDATION_STORE_KEY );

	const { hasValidationErrors } = useSelect( ( select ) => {
		const store = select( VALIDATION_STORE_KEY );
		return {
			hasValidationErrors: store.hasValidationErrors,
		};
	} );

	const validateSubmit = () => {
		showAllValidationErrors();
		return ! hasValidationErrors();
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
				{ __( 'Update', 'woo-gutenberg-products-block' ) }
			</Button>
		</form>
	);
};

export default ShippingCalculatorAddress;
