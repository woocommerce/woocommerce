/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import Button from '@woocommerce/base-components/button';
import { useState, useCallback } from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import type { ShippingAddress, AddressForm } from '@woocommerce/settings';
import { validationStore, cartStore } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { useFocusReturn } from '@woocommerce/base-utils';
import { useCheckoutAddress } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';
import { Form } from '../form';
import { useFormFields } from '../form/use-form-fields';

interface ShippingCalculatorAddressProps {
	address: ShippingAddress;
	onUpdate: ( address: Partial< ShippingAddress > ) => void;
	onCancel: () => void;
	addressFields: Partial< keyof AddressForm >[];
}
const ShippingCalculatorAddress = ( {
	address: initialAddress,
	onUpdate,
	onCancel,
	addressFields,
}: ShippingCalculatorAddressProps ): JSX.Element => {
	const [ address, setAddress ] = useState( initialAddress );
	const { showAllValidationErrors } = useDispatch( validationStore );
	const focusReturnRef = useFocusReturn();
	const { hasValidationErrors, isCustomerDataUpdating } = useSelect(
		( select ) => {
			return {
				hasValidationErrors:
					select( validationStore ).hasValidationErrors(),
				isCustomerDataUpdating:
					select( cartStore ).isCustomerDataUpdating(),
			};
		},
		[]
	);
	const { defaultFields } = useCheckoutAddress();
	const formFields = useFormFields(
		addressFields,
		defaultFields,
		'shipping',
		address.country
	);

	const hasRequiredFields = useCallback( () => {
		for ( const field of formFields ) {
			if ( field.required && ! field.hidden ) {
				const value = address[ field.key ];

				if ( typeof value === 'string' ) {
					if ( value.trim() === '' ) {
						return false;
					}
					continue;
				}

				// TODO: Handle boolean fields if needed
				// Currently, any non-string value fails validation
				return false;
			}
		}
		return true;
	}, [ formFields, address ] );

	const handleClick = useCallback(
		( e: React.MouseEvent< HTMLButtonElement > ) => {
			e.preventDefault();

			showAllValidationErrors();

			const isAddressValid = ! hasValidationErrors && hasRequiredFields();

			if ( isAddressValid ) {
				const addressChanged = ! isShallowEqual(
					address,
					initialAddress
				);

				if ( ! addressChanged ) {
					return onCancel();
				}

				const addressToSubmit: Partial< ShippingAddress > =
					Object.fromEntries(
						addressFields
							.filter( ( key ) => address[ key ] !== undefined )
							.map( ( key ) => [ key, address[ key ] ] )
					);

				onUpdate( addressToSubmit );
			}
		},
		[
			showAllValidationErrors,
			hasValidationErrors,
			hasRequiredFields,
			address,
			initialAddress,
			addressFields,
			onCancel,
			onUpdate,
		]
	);

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
				variant="outlined"
				onClick={ handleClick }
				type="submit"
			>
				{ __( 'Check delivery options', 'woocommerce' ) }
			</Button>
		</form>
	);
};

export default ShippingCalculatorAddress;
