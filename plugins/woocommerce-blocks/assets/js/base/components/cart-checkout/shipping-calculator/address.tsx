/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { useState } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import type { ShippingAddress, FormFields } from '@woocommerce/settings';
import { VALIDATION_STORE_KEY, CART_STORE_KEY } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useFocusReturn } from '@woocommerce/base-utils';
/**
 * Internal dependencies
 */
import './style.scss';
import { Form } from '../form';

interface ShippingCalculatorAddressProps {
	address: ShippingAddress;
	onUpdate: ( address: ShippingAddress ) => void;
	onCancel: () => void;
	addressFields: Partial< keyof FormFields >[];
}
const ShippingCalculatorAddress = ( {
	address: initialAddress,
	onUpdate,
	onCancel,
	addressFields,
}: ShippingCalculatorAddressProps ): JSX.Element => {
	const [ address, setAddress ] = useState( initialAddress );
	const { showAllValidationErrors } = useDispatch( VALIDATION_STORE_KEY );
	const focusReturnRef = useFocusReturn();
	const { hasValidationErrors, isCustomerDataUpdating } = useSelect(
		( select ) => {
			return {
				hasValidationErrors:
					select( VALIDATION_STORE_KEY ).hasValidationErrors,
				isCustomerDataUpdating:
					select( CART_STORE_KEY ).isCustomerDataUpdating(),
			};
		}
	);

	const validateSubmit = () => {
		showAllValidationErrors();
		return ! hasValidationErrors();
	};

	return (
		<form
			className="wc-block-components-shipping-calculator-address"
			ref={ focusReturnRef }
		>
			<Form
				fields={ addressFields }
				onChange={ setAddress }
				values={ address }
			/>
			<Button
				className="wc-block-components-shipping-calculator-address__button"
				disabled={ isCustomerDataUpdating }
				onClick={ ( e ) => {
					e.preventDefault();
					const addressChanged = ! isShallowEqual(
						address,
						initialAddress
					);

					if ( ! addressChanged ) {
						return onCancel();
					}

					const isAddressValid = validateSubmit();

					if ( isAddressValid ) {
						const addressToSubmit = {};
						addressFields.forEach( ( key ) => {
							if ( typeof address[ key ] !== 'undefined' ) {
								addressToSubmit[ key ] = address[ key ];
							}
						} );
						return onUpdate( addressToSubmit );
					}
				} }
				type="submit"
			>
				{ __( 'Update', 'woocommerce' ) }
			</Button>
		</form>
	);
};

export default ShippingCalculatorAddress;
