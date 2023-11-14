/**
 * External dependencies
 */
import { useMemo, Fragment } from '@wordpress/element';
import { useEffectOnce } from 'usehooks-ts';
import {
	useCheckoutAddress,
	useEditorContext,
	noticeContexts,
} from '@woocommerce/base-context';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import type {
	ShippingAddress,
	AddressField,
	AddressFields,
} from '@woocommerce/settings';
import { StoreNoticesContainer } from '@woocommerce/blocks-checkout';
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';

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
	forceEditing = false,
}: {
	showCompanyField: boolean;
	showApartmentField: boolean;
	showPhoneField: boolean;
	requireCompanyField: boolean;
	requirePhoneField: boolean;
	forceEditing?: boolean;
} ): JSX.Element => {
	const { billingAddress, setShippingAddress, useBillingAsShipping } =
		useCheckoutAddress();
	const { isEditor } = useEditorContext();

	// Syncs shipping address with billing address if "Force shipping to the customer billing address" is enabled.
	useEffectOnce( () => {
		if ( useBillingAsShipping ) {
			const { email, ...addressValues } = billingAddress;
			const syncValues: Partial< ShippingAddress > = {
				...addressValues,
			};

			if ( ! showPhoneField ) {
				delete syncValues.phone;
			}

			if ( showCompanyField ) {
				delete syncValues.company;
			}

			setShippingAddress( syncValues );
		}
	} );

	const addressFieldsConfig = useMemo( () => {
		return {
			company: {
				hidden: ! showCompanyField,
				required: requireCompanyField,
			},
			address_2: {
				hidden: ! showApartmentField,
			},
		};
	}, [
		showCompanyField,
		requireCompanyField,
		showApartmentField,
	] ) as Record< keyof AddressFields, Partial< AddressField > >;

	const WrapperComponent = isEditor ? Noninteractive : Fragment;
	const noticeContext = useBillingAsShipping
		? [ noticeContexts.BILLING_ADDRESS, noticeContexts.SHIPPING_ADDRESS ]
		: [ noticeContexts.BILLING_ADDRESS ];

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
						showPhoneField={ showPhoneField }
						requirePhoneField={ requirePhoneField }
						forceEditing={ forceEditing }
					/>
				) : null }
			</WrapperComponent>
		</>
	);
};

export default Block;
