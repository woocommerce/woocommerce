/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { Form } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutAddress,
	useStoreEvents,
	useCustomerData,
} from '@woocommerce/base-context';
import type { ShippingAddress } from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { ADDRESS_FORM_KEYS } from '@woocommerce/block-settings';
import { startLayoutChange } from '@woocommerce/base-utils/use-pending-layout-change';

/**
 * Internal dependencies
 */
import AddressWrapper from '../../address-wrapper';
import AddressCard from '../../address-card';

const CustomerAddress = () => {
	const {
		shippingAddress,
		setShippingAddress,
		setBillingAddress,
		useShippingAsBilling,
		editingShippingAddress,
		setEditingShippingAddress,
	} = useCheckoutAddress();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const [ shouldAnimate, setShouldAnimate ] = useState( false );
	const wrapperRef = useRef< HTMLDivElement >( null );

	const { isInitialized } = useCustomerData();

	const { validationErrors } = useSelect(
		( select ) => {
			return {
				validationErrors:
					select( validationStore ).getValidationErrors(),
			};
		},
		[ shippingAddress ]
	);

	useEffect( () => {
		// Check if any shipping field has validation errors
		const hasValidationErrors = Object.keys( shippingAddress ).some(
			( key ) => {
				// Check if 'shipping_' + key exists in validationErrors
				return validationErrors[ `shipping_${ key }` ] !== undefined;
			}
		);

		// Forces editing state if store has errors,
		// but not on initial render when all fields are empty.
		if (
			isInitialized &&
			hasValidationErrors &&
			editingShippingAddress === false
		) {
			setEditingShippingAddress( true );
		}
	}, [
		editingShippingAddress,
		shippingAddress,
		isInitialized,
		setEditingShippingAddress,
		validationErrors,
	] );

	const onChangeAddress = useCallback(
		( values: ShippingAddress ) => {
			setShippingAddress( values );
			if ( useShippingAsBilling ) {
				setBillingAddress( values );
				dispatchCheckoutEvent( 'set-billing-address' );
			}
			dispatchCheckoutEvent( 'set-shipping-address' );
		},
		[
			dispatchCheckoutEvent,
			setBillingAddress,
			setShippingAddress,
			useShippingAsBilling,
		]
	);

	const handleEditClick = useCallback( () => {
		// Grow the card into the form where supported; otherwise fall back to the `.is-animated` CSS reveal.
		if (
			! startLayoutChange( () => setEditingShippingAddress( true ), {
				morph: wrapperRef.current,
			} )
		) {
			setShouldAnimate( true );
		}
	}, [ setEditingShippingAddress ] );

	return (
		<AddressWrapper
			isEditing={ editingShippingAddress }
			shouldAnimate={ shouldAnimate }
			wrapperRef={ wrapperRef }
			addressCard={
				<AddressCard
					address={ shippingAddress }
					target="shipping"
					onEdit={ handleEditClick }
					isExpanded={ editingShippingAddress }
				/>
			}
			addressForm={
				<Form< ShippingAddress >
					id="shipping"
					addressType="shipping"
					onChange={ onChangeAddress }
					values={ shippingAddress }
					fields={ ADDRESS_FORM_KEYS }
					isEditing={ editingShippingAddress }
				/>
			}
		/>
	);
};

export default CustomerAddress;
