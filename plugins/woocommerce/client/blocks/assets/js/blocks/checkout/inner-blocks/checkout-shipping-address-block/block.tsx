/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
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
import type { BillingAddress } from '@woocommerce/settings';
import { getSetting } from '@woocommerce/settings';
import { emptyAddressFields } from '@woocommerce/base-utils';
import type { CartResponseBillingAddress } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import CustomerAddress from './customer-address';

const Block = (): JSX.Element => {
	const {
		defaultFields,
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

		if ( defaultFields?.phone?.hidden ) {
			delete syncValues.phone;
		}

		if ( defaultFields?.company?.hidden ) {
			delete syncValues.company;
		}

		// Only sync if any values are different
		const needsSync =
			Object.keys( syncValues ).length !==
				Object.keys( billingAddress ).length ||
			! Object.keys( syncValues ).every(
				( key ) =>
					syncValues[ key as keyof BillingAddress ] ===
					billingAddress[ key as keyof BillingAddress ]
			);

		if ( needsSync ) {
			setBillingAddress( syncValues );
		}
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

	const WrapperComponent = isEditor ? Noninteractive : Fragment;
	const noticeContext = useShippingAsBilling
		? [ noticeContexts.SHIPPING_ADDRESS, noticeContexts.BILLING_ADDRESS ]
		: [ noticeContexts.SHIPPING_ADDRESS ];

	return (
		<>
			<StoreNoticesContainer context={ noticeContext } />
			<WrapperComponent>
				<CustomerAddress />
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
