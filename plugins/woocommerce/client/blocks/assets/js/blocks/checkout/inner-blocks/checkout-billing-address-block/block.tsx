/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { useEffectOnce } from 'usehooks-ts';
import {
	useCheckoutAddress,
	useEditorContext,
	noticeContexts,
} from '@woocommerce/base-context';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { ShippingAddress } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CustomerAddress from './customer-address';

const Block = (): JSX.Element => {
	const {
		defaultFields,
		billingAddress,
		setShippingAddress,
		useBillingAsShipping,
	} = useCheckoutAddress();
	const { isEditor } = useEditorContext();

	// Syncs shipping address with billing address if "Force shipping to the customer billing address" is enabled.
	useEffectOnce( () => {
		if ( useBillingAsShipping ) {
			const { email, ...addressValues } = billingAddress;
			const syncValues: Partial< ShippingAddress > = {
				...addressValues,
			};

			if ( defaultFields?.phone?.hidden ) {
				delete syncValues.phone;
			}

			if ( defaultFields?.company?.hidden ) {
				delete syncValues.company;
			}

			setShippingAddress( syncValues );
		}
	} );

	const WrapperComponent = isEditor ? Noninteractive : Fragment;
	const noticeContext = useBillingAsShipping
		? [ noticeContexts.BILLING_ADDRESS, noticeContexts.SHIPPING_ADDRESS ]
		: [ noticeContexts.BILLING_ADDRESS ];

	return (
		<>
			<StoreNoticesContainer context={ noticeContext } />
			<WrapperComponent>
				<CustomerAddress />
			</WrapperComponent>
		</>
	);
};

export default Block;
