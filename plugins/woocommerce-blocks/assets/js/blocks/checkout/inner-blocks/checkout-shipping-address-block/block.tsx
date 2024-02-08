/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, Fragment, useRef } from '@wordpress/element';
import { useEffectOnce } from 'usehooks-ts';
import {
	useCheckoutAddress,
	useEditorContext,
	noticeContexts,
} from '@woocommerce/base-context';
import {
	StoreNoticesContainer,
	CheckboxControl,
} from '@woocommerce/blocks-components';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import type { BillingAddress, FormFieldsConfig } from '@woocommerce/settings';
import { useSelect, dispatch } from '@wordpress/data';
import { CART_STORE_KEY, processErrorResponse } from '@woocommerce/block-data';
import {
	emptyAddressFields,
	removeNoticesWithContext,
} from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import CustomerAddress from './customer-address';

const Block = ( {
	showCompanyField = false,
	showApartmentField = false,
	showPhoneField = false,
	requireCompanyField = false,
	requirePhoneField = false,
}: {
	showCompanyField: boolean;
	showApartmentField: boolean;
	showPhoneField: boolean;
	requireCompanyField: boolean;
	requirePhoneField: boolean;
} ): JSX.Element => {
	const {
		setBillingAddress,
		shippingAddress,
		billingAddress,
		useShippingAsBilling,
		setUseShippingAsBilling,
	} = useCheckoutAddress();
	const { isEditor } = useEditorContext();

	const previousBillingAddressRef = useRef< BillingAddress | null >( null );

	// Syncs the billing address with the shipping address.
	const syncBillingWithShipping = () => {
		const syncValues: Partial< BillingAddress > = {
			...shippingAddress,
		};

		if ( ! showPhoneField ) {
			delete syncValues.phone;
		}

		if ( showCompanyField ) {
			delete syncValues.company;
		}

		previousBillingAddressRef.current = billingAddress;
		setBillingAddress( syncValues );
	};

	// Syncs the billing address on the server with the new address.
	const syncBillingAddress = ( newAddress: BillingAddress ) => {
		const noticeContext = 'wc/checkout/billing-address';
		dispatch( CART_STORE_KEY )
			.updateCustomerData(
				{
					billing_address: newAddress,
				},
				false
			)
			.then( () => {
				removeNoticesWithContext( noticeContext );
			} )
			.catch( ( response ) => {
				processErrorResponse( response, noticeContext );
			} );
	};

	const deSyncBillingWithShipping = () => {
		// If we have a previous billing address, use that.
		// Otherwise, create an empty address.
		if ( previousBillingAddressRef.current ) {
			setBillingAddress( previousBillingAddressRef.current );
			syncBillingAddress( previousBillingAddressRef.current );
		} else {
			const emptyAddress = emptyAddressFields( billingAddress );
			setBillingAddress( emptyAddress );
			syncBillingAddress( emptyAddress );
		}
	};

	// Run this on first render to ensure addresses sync if needed (this is not re-ran when toggling the checkbox).
	useEffectOnce( () => {
		if ( useShippingAsBilling ) {
			syncBillingWithShipping();
		}
	} );

	// Create address fields config from block attributes.
	const addressFieldsConfig = useMemo( () => {
		return {
			company: {
				hidden: ! showCompanyField,
				required: requireCompanyField,
			},
			address_2: {
				hidden: ! showApartmentField,
			},
			phone: {
				hidden: ! showPhoneField,
				required: requirePhoneField,
			},
		};
	}, [
		showCompanyField,
		requireCompanyField,
		showApartmentField,
		showPhoneField,
		requirePhoneField,
	] ) as FormFieldsConfig;

	const WrapperComponent = isEditor ? Noninteractive : Fragment;
	const noticeContext = useShippingAsBilling
		? [ noticeContexts.SHIPPING_ADDRESS, noticeContexts.BILLING_ADDRESS ]
		: [ noticeContexts.SHIPPING_ADDRESS ];
	const hasAddress = !! (
		shippingAddress.address_1 &&
		( shippingAddress.first_name || shippingAddress.last_name )
	);

	const { cartDataLoaded } = useSelect( ( select ) => {
		const store = select( CART_STORE_KEY );
		return {
			cartDataLoaded: store.hasFinishedResolution( 'getCartData' ),
		};
	} );

	// Default editing state for CustomerAddress component comes from the current address and whether or not we're in the editor.
	const defaultEditingAddress = isEditor || ! hasAddress;

	return (
		<>
			<StoreNoticesContainer context={ noticeContext } />
			<WrapperComponent>
				{ cartDataLoaded ? (
					<CustomerAddress
						addressFieldsConfig={ addressFieldsConfig }
						defaultEditing={ defaultEditingAddress }
					/>
				) : null }
			</WrapperComponent>
			<CheckboxControl
				className="wc-block-checkout__use-address-for-billing"
				label={ __( 'Use same address for billing', 'woocommerce' ) }
				checked={ useShippingAsBilling }
				onChange={ ( checked: boolean ) => {
					setUseShippingAsBilling( checked );
					if ( checked ) {
						syncBillingWithShipping();
					} else {
						deSyncBillingWithShipping();
					}
				} }
			/>
		</>
	);
};

export default Block;
