/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, Fragment } from '@wordpress/element';
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
import { getSetting } from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { emptyAddressFields } from '@woocommerce/base-utils';
import type { CartResponseBillingAddress } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import CustomerAddress from './customer-address';

const Block = ( {
	showCompanyField = false,
	requireCompanyField = false,
	showApartmentField = false,
	requireApartmentField = false,
	showPhoneField = false,
	requirePhoneField = false,
}: {
	showCompanyField: boolean;
	requireCompanyField: boolean;
	showApartmentField: boolean;
	requireApartmentField: boolean;
	showPhoneField: boolean;
	requirePhoneField: boolean;
} ): JSX.Element => {
	const {
		setBillingAddress,
		shippingAddress,
		billingAddress,
		useShippingAsBilling,
		setUseShippingAsBilling,
		setEditingBillingAddress,
	} = useCheckoutAddress();
	const { isEditor } = useEditorContext();
	const isGuest = getSetting( 'currentUserId' ) === 0;

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

		setBillingAddress( syncValues );
	};

	const clearBillingAddress = ( address: BillingAddress ) => {
		// If the address is empty or the user is not a guest,
		// we don't need to clear the address.
		if ( ! address || ! isGuest ) {
			return;
		}
		const emptyAddress = emptyAddressFields(
			address as CartResponseBillingAddress
		);
		setBillingAddress( emptyAddress );
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
				required: requireApartmentField,
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
		requireApartmentField,
		showPhoneField,
		requirePhoneField,
	] ) as FormFieldsConfig;

	const WrapperComponent = isEditor ? Noninteractive : Fragment;
	const noticeContext = useShippingAsBilling
		? [ noticeContexts.SHIPPING_ADDRESS, noticeContexts.BILLING_ADDRESS ]
		: [ noticeContexts.SHIPPING_ADDRESS ];

	const { cartDataLoaded } = useSelect( ( select ) => {
		const store = select( CART_STORE_KEY );
		return {
			cartDataLoaded: store.hasFinishedResolution( 'getCartData' ),
		};
	} );

	return (
		<>
			<StoreNoticesContainer context={ noticeContext } />
			<WrapperComponent>
				{ cartDataLoaded ? (
					<CustomerAddress
						addressFieldsConfig={ addressFieldsConfig }
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
						setEditingBillingAddress( true );
						clearBillingAddress( billingAddress );
					}
				} }
			/>
		</>
	);
};

export default Block;
