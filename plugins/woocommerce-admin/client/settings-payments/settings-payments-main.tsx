/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { PAYMENT_GATEWAYS_STORE_NAME, PaymentGateway } from '@woocommerce/data';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './settings-payments-main.scss';
import { PaymentMethod } from './components/payment-method';
import { OtherPaymentMethods } from './components/other-payment-methods';
import { PaymentsBannerWrapper } from '~/payments/payment-settings-banner';

export const SettingsPaymentsMain: React.FC = () => {
	const paymentGateways = useSelect( ( select ) =>
		select( PAYMENT_GATEWAYS_STORE_NAME ).getPaymentGateways()
	);

	const isLoading = useSelect(
		( select ) =>
			! select( PAYMENT_GATEWAYS_STORE_NAME ).hasFinishedResolution(
				'getPaymentGateways'
			)
	);

	const availablePaymentGateways = useMemo( () => {
		const isWcPayInstalled = paymentGateways.some(
			( gateway: PaymentGateway ) => {
				return gateway.id === 'woocommerce_payments';
			}
		);

		if ( isWcPayInstalled ) {
			return paymentGateways.filter( ( gateway: PaymentGateway ) => {
				// Filter out woocommerce payment sub-methods. e.g woocommerce_payments_bancontact and pre_install_woocommerce_payments_promotion
				return (
					! gateway.id.startsWith( 'woocommerce_payments_' ) &&
					gateway.id !== 'pre_install_woocommerce_payments_promotion'
				);
			} );
		}

		return paymentGateways;
	}, [ paymentGateways ] );

	return (
		<div className="settings-payments-main__container">
			<div id="wc_payments_settings_slotfill">
				<PaymentsBannerWrapper />
			</div>

			<table className="form-table">
				<tbody>
					<tr>
						<td
							className="wc_payment_gateways_wrapper"
							colSpan={ 2 }
						>
							<table
								className="wc_gateways widefat"
								cellSpacing="0"
								aria-describedby="payment_gateways_options-description"
							>
								<thead>
									<tr>
										<th className="sort"></th>
										<th className="name">
											{ __( 'Method', 'woocommerce' ) }
										</th>
										<th className="status">
											{ __( 'Enabled', 'woocommerce' ) }
										</th>
										<th className="description">
											{ __(
												'Description',
												'woocommerce'
											) }
										</th>
										<th className="action"></th>
									</tr>
								</thead>

								{ isLoading ? (
									<div className="settings-payments-main__spinner">
										<Spinner />
									</div>
								) : (
									<tbody className="ui-sortable">
										{ availablePaymentGateways.map(
											( gateway: PaymentGateway ) => (
												<PaymentMethod
													key={ gateway.id }
													{ ...gateway }
												/>
											)
										) }

										<tr>
											<td
												className="other-payment-methods-row"
												colSpan={ 5 }
											>
												<OtherPaymentMethods />
											</td>
										</tr>
									</tbody>
								) }
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	);
};

export default SettingsPaymentsMain;
