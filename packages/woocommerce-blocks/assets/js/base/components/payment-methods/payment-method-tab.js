/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useCheckoutContext,
	useEditorContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import PaymentMethodErrorBoundary from './payment-method-error-boundary';

/**
 * Component used to render the contents of a payment method tab.
 *
 * @param {Object}  props              Incoming props for the component.
 * @param {boolean} props.allowsSaving Whether that payment method allows saving
 *                                     the data for future purchases.
 * @param {Object}  props.children     Content of the payment method tab.
 *
 * @return {*} The rendered component.
 */
const PaymentMethodTab = ( { children, allowsSaving } ) => {
	const { isEditor } = useEditorContext();
	const {
		shouldSavePayment,
		setShouldSavePayment,
	} = usePaymentMethodDataContext();
	const { customerId } = useCheckoutContext();

	return (
		<PaymentMethodErrorBoundary isEditor={ isEditor }>
			{ children }
			{ customerId > 0 && allowsSaving && (
				<CheckboxControl
					className="wc-block-components-payment-methods__save-card-info"
					label={ __(
						'Save payment information to my account for future purchases.',
						'woocommerce'
					) }
					checked={ shouldSavePayment }
					onChange={ () =>
						setShouldSavePayment( ! shouldSavePayment )
					}
				/>
			) }
		</PaymentMethodErrorBoundary>
	);
};

PaymentMethodTab.propTypes = {
	allowsSaving: PropTypes.bool,
	children: PropTypes.node,
};

export default PaymentMethodTab;
