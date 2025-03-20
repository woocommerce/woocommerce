/**
 * External dependencies
 */
import { type OfflinePaymentMethodProvider } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { OfflinePaymentGatewayList } from '../offline-gateway-list-item/offline-payment-gateway-list-item';
import { ListPlaceholder } from '~/settings-payments/components/list-placeholder';
import './offline-payment-gateways.scss';

interface OfflinePaymentGatewaysProps {
	/**
	 * Indicates if the data for offline payment gateways is still being fetched.
	 */
	isFetching: boolean;
	/**
	 * Array of offline payment gateways to display.
	 */
	offlinePaymentGateways: OfflinePaymentMethodProvider[];
	/**
	 * Callback function to update the ordering of the gateways after sorting.
	 */
	updateOrdering: ( gateways: OfflinePaymentMethodProvider[] ) => void;
}

/**
 * A component that renders the list of offline payment gateways in the WooCommerce settings.
 * Displays a loading placeholder while data is being fetched and the list of gateways once available.
 */
export const OfflinePaymentGateways = ( {
	isFetching,
	offlinePaymentGateways,
	updateOrdering,
}: OfflinePaymentGatewaysProps ) => {
	return (
		<div className="settings-payment-gateways">
			<div className="settings-payment-gateways__header">
				<div className="settings-payment-gateways__header-title">
					{ __( 'Payment methods', 'woocommerce' ) }
				</div>
			</div>
			{ isFetching ? (
				<ListPlaceholder rows={ 3 } />
			) : (
				<OfflinePaymentGatewayList
					gateways={ offlinePaymentGateways }
					setGateways={ updateOrdering }
				/>
			) }
		</div>
	);
};
