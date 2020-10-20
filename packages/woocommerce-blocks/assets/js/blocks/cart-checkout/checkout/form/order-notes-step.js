/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormStep } from '@woocommerce/base-components/cart-checkout';
import {
	useCheckoutContext,
	useShippingDataContext,
} from '@woocommerce/base-context';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import CheckoutOrderNotes from './order-notes';

const OrderNotesStep = ( { showOrderNotes } ) => {
	const { needsShipping } = useShippingDataContext();
	const {
		isProcessing: checkoutIsProcessing,
		orderNotes,
		dispatchActions,
	} = useCheckoutContext();
	const { setOrderNotes } = dispatchActions;

	if ( ! showOrderNotes ) {
		return null;
	}

	return (
		<FormStep id="order-notes" showStepNumber={ false }>
			<CheckoutOrderNotes
				disabled={ checkoutIsProcessing }
				onChange={ setOrderNotes }
				placeholder={
					needsShipping
						? __(
								'Notes about your order, e.g. special notes for delivery.',
								'woocommerce'
						  )
						: __(
								'Notes about your order.',
								'woocommerce'
						  )
				}
				value={ orderNotes }
			/>
		</FormStep>
	);
};

OrderNotesStep.propTypes = {
	showOrderNotes: PropTypes.bool.isRequired,
};

export default OrderNotesStep;
