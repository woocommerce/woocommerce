/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutContext,
	useShippingDataContext,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import CheckoutOrderNotes from '../../../checkout/form/order-notes';

const Block = (): JSX.Element => {
	const { needsShipping } = useShippingDataContext();
	const {
		isProcessing: checkoutIsProcessing,
		orderNotes,
		dispatchActions,
	} = useCheckoutContext();
	const { setOrderNotes } = dispatchActions;

	return (
		<FormStep
			id="order-notes"
			showStepNumber={ false }
			className="wc-block-checkout__order-notes"
			disabled={ checkoutIsProcessing }
		>
			<CheckoutOrderNotes
				disabled={ checkoutIsProcessing }
				onChange={ setOrderNotes }
				placeholder={
					needsShipping
						? __(
								'Notes about your order, e.g. special notes for delivery.',
								'woo-gutenberg-products-block'
						  )
						: __(
								'Notes about your order.',
								'woo-gutenberg-products-block'
						  )
				}
				value={ orderNotes }
			/>
		</FormStep>
	);
};

export default Block;
