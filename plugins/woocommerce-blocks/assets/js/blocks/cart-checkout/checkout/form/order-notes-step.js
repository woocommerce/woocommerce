/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutContext,
	useShippingDataContext,
} from '@woocommerce/base-context';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import CheckoutOrderNotes from './order-notes';

const OrderNotesStep = () => {
	const { needsShipping } = useShippingDataContext();
	const { orderNotes, dispatchActions } = useCheckoutContext();
	const { isDisabled } = useCheckoutSubmit();
	const { setOrderNotes } = dispatchActions;

	return (
		<FormStep
			id="order-notes"
			showStepNumber={ false }
			className="wc-block-checkout__order-notes"
			disabled={ isDisabled }
		>
			<CheckoutOrderNotes
				onChange={ setOrderNotes }
				disabled={ isDisabled }
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

export default OrderNotesStep;
