/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import {
	type OfflinePaymentMethodProvider,
	paymentSettingsStore,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './settings-payments-offline.scss';
import './settings-payments-body.scss';
import { OfflinePaymentGateways } from './components/offline-payment-gateways';
import { BankAccountsList } from '~/settings-payments/components/bank-accounts-table';

/**
 * A component for managing offline payment gateways in WooCommerce.
 * It retrieves and displays a list of offline payment gateways,
 * allows users to reorder them, and updates the order in the store.
 */
export const SettingsPaymentsOffline = () => {
	// Retrieve offline payment gateways and loading state from the store.
	const { offlinePaymentGateways, isFetching } = useSelect( ( select ) => {
		const paymentSettings = select( paymentSettingsStore );
		return {
			isFetching: paymentSettings.isFetching(),
			offlinePaymentGateways: paymentSettings.getOfflinePaymentGateways(),
		};
	}, [] );

	// Dispatch function to update the ordering of payment gateways.
	const { updateProviderOrdering } = useDispatch( paymentSettingsStore );
	// State to hold the sorted gateways in case of changing the order, otherwise it will be null
	const [ sortedOfflinePaymentGateways, setSortedOfflinePaymentGateways ] =
		useState< OfflinePaymentMethodProvider[] | null >( null );

	/**
	 * Clear sortedOfflinePaymentGateways when data store updates.
	 */
	useEffect( () => {
		setSortedOfflinePaymentGateways( null );
	}, [ offlinePaymentGateways ] );

	/**
	 * Handles updating the order of offline payment gateways.
	 */
	function handleOrderingUpdate( sorted: OfflinePaymentMethodProvider[] ) {
		// Extract the existing _order values in the sorted order
		const updatedOrderValues = sorted
			.map( ( gateway ) => gateway._order )
			.sort( ( a, b ) => a - b );

		// Build the orderMap by assigning the sorted _order values
		const orderMap: Record< string, number > = {};
		sorted.forEach( ( gateway, index ) => {
			orderMap[ gateway.id ] = updatedOrderValues[ index ];
		} );

		updateProviderOrdering( orderMap );

		// Set the sorted providers to the state to give a real-time update
		setSortedOfflinePaymentGateways( sorted );
	}

	return (
		<div className="settings-payments-offline__container">
			<OfflinePaymentGateways
				isFetching={ isFetching }
				updateOrdering={ handleOrderingUpdate }
				offlinePaymentGateways={
					sortedOfflinePaymentGateways || offlinePaymentGateways
				}
			/>

			<br />
			<br />

			<BankAccountsList
				accounts={ [
					{
						id: 'acc-1',
						account_name: 'Red Potato Shop Inc',
						account_number: '123456789',
						bank_name: 'Bank of America',
						routing_number: '111000025',
						sort_code: '123456',
						iban: 'US12345678901234567890',
						bic: 'BOFAUS3N',
					},
					{
						id: 'acc-2',
						account_name: 'Sarah Lee',
						account_number: '654321098',
						bank_name: 'Commonwealth Bank',
						routing_number: '062000',
						sort_code: '062000',
						iban: 'AU12345678901234567890',
						bic: 'CTBAAU2S',
					},
					{
						id: 'acc-3',
						account_name: 'Max Müller',
						account_number: 'DE44500105175407324931',
						bank_name: 'Deutsche Bank',
						routing_number: '50010517',
						sort_code: '50010517',
						iban: 'DE44500105175407324931',
						bic: 'DEUTDEFF',
					},
				] }
				onChange={ ( updatedAccounts ) =>
					console.log( 'Updated accounts:', updatedAccounts )
				}
				defaultCountry="US"
			/>
		</div>
	);
};

export default SettingsPaymentsOffline;
