/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import { getIconsFromPaymentMethods } from '@woocommerce/base-utils';
import PaymentMethodIcons from '@woocommerce/base-components/cart-checkout/payment-method-icons';
import { PaymentEventsProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';

const PaymentMethodIconsElement = (): JSX.Element => {
	const paymentMethods = getPaymentMethods();

	return (
		<PaymentMethodIcons
			icons={ getIconsFromPaymentMethods( paymentMethods ) }
		/>
	);
};

const Edit = () => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-payment-methods">
				<PaymentEventsProvider>
					<PaymentMethodIconsElement />
				</PaymentEventsProvider>
			</div>
		</div>
	);
};

export default Edit;
