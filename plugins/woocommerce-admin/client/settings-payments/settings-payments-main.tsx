/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PaymentGateway } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './settings-payments-main.scss';
import { PaymentMethod } from './components/payment-method';
import { OtherPaymentMethods } from './components/other-payment-methods';
import { PaymentsBannerWrapper } from '~/payments/payment-settings-banner';

export const SettingsPaymentsMain: React.FC = () => {
	const [ paymentGateways, error ] = useMemo( () => {
		const script = document.getElementById(
			'experimental_wc_settings_payments_gateways'
		);

		try {
			if ( script && script.textContent ) {
				return [
					JSON.parse( script.textContent ) as PaymentGateway[],
					null,
				];
			}
			throw new Error( 'Could not find payment gateways data' );
		} catch ( e ) {
			return [ [], e as Error ];
		}
	}, [] );

	if ( error ) {
		// This is a temporary error message to be replaced by error boundary.
		return (
			<div>
				<h1>
					{ __( 'Error loading payment gateways', 'woocommerce' ) }
				</h1>
				<p>{ error.message }</p>
			</div>
		);
	}

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
								<tbody className="ui-sortable">
									{ paymentGateways.map(
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
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	);
};

export default SettingsPaymentsMain;
