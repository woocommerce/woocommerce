/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WooPaymentsAccountSettings } from '~/woopayments/settings/account-settings';
import {
	getWooPaymentsDepositsOverview,
	getWooPaymentsRecentDeposits,
	submitWooPaymentsInstantDeposit,
} from './data';
import { AccountBalancesCard } from './components/account-balances-card';
import { PayoutsOverviewCard } from './components/payouts-overview-card';
import type { WooPaymentsDeposit, WooPaymentsDepositsOverview } from './types';
import { getSelectedBalanceCurrency } from './utils';
import { SpotlightPromotion } from '../../promotions/spotlight';

const getErrorMessage = ( error: unknown ) => {
	if ( error instanceof Error && error.message ) {
		return error.message;
	}

	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof error.message === 'string'
	) {
		return error.message;
	}

	return __( 'Unable to load WooPayments payout data.', 'woocommerce' );
};

export const WooPaymentsOverviewPage = () => {
	const [ overview, setOverview ] =
		useState< WooPaymentsDepositsOverview | null >( null );
	const [ recentPayouts, setRecentPayouts ] = useState<
		WooPaymentsDeposit[]
	>( [] );
	const [ selectedCurrency, setSelectedCurrency ] = useState< string | null >(
		null
	);
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isPayoutsLoading, setIsPayoutsLoading ] = useState( false );
	const [ overviewErrorMessage, setOverviewErrorMessage ] = useState<
		string | null
	>( null );
	const [ payoutsErrorMessage, setPayoutsErrorMessage ] = useState<
		string | null
	>( null );

	const reloadOverviewAndPayouts = async ( currency: string ) => {
		const deposit = await submitWooPaymentsInstantDeposit( currency );
		const [ nextOverview, recent ] = await Promise.all( [
			getWooPaymentsDepositsOverview(),
			getWooPaymentsRecentDeposits( currency ),
		] );

		setOverview( nextOverview );
		setOverviewErrorMessage( null );
		setSelectedCurrency(
			getSelectedBalanceCurrency( nextOverview, currency )
		);
		setRecentPayouts( recent.data );
		setPayoutsErrorMessage( null );

		return deposit;
	};

	useEffect( () => {
		let isMounted = true;

		const loadOverview = async () => {
			setIsLoading( true );

			try {
				const nextOverview = await getWooPaymentsDepositsOverview();
				const currency = getSelectedBalanceCurrency(
					nextOverview,
					null
				);

				if ( ! isMounted ) {
					return;
				}

				setOverview( nextOverview );
				setOverviewErrorMessage( null );
				setSelectedCurrency( currency );
			} catch ( error ) {
				if ( isMounted ) {
					setOverview( null );
					setOverviewErrorMessage( getErrorMessage( error ) );
					setSelectedCurrency( '' );
				}
			} finally {
				if ( isMounted ) {
					setIsLoading( false );
				}
			}
		};

		loadOverview();

		return () => {
			isMounted = false;
		};
	}, [] );

	useEffect( () => {
		if ( selectedCurrency === null ) {
			return;
		}

		let isMounted = true;

		const loadRecentPayouts = async () => {
			setIsPayoutsLoading( true );

			try {
				const recent = await getWooPaymentsRecentDeposits(
					selectedCurrency
				);

				if ( isMounted ) {
					setRecentPayouts( recent.data );
					setPayoutsErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setRecentPayouts( [] );
					setPayoutsErrorMessage( getErrorMessage( error ) );
				}
			} finally {
				if ( isMounted ) {
					setIsPayoutsLoading( false );
				}
			}
		};

		loadRecentPayouts();

		return () => {
			isMounted = false;
		};
	}, [ selectedCurrency ] );

	return (
		<div className="woocommerce-woopayments-overview__content">
			<WooPaymentsAccountSettings />
			<SpotlightPromotion />
			<div className="woocommerce-woopayments-overview__cards">
				<AccountBalancesCard
					isLoading={ isLoading }
					errorMessage={ overviewErrorMessage }
					overview={ overview }
					selectedCurrency={ selectedCurrency || undefined }
					onCurrencyChange={ setSelectedCurrency }
					onInstantPayoutSubmit={ reloadOverviewAndPayouts }
				/>
				<PayoutsOverviewCard
					isLoading={ isLoading || isPayoutsLoading }
					errorMessage={ payoutsErrorMessage }
					overview={ overview }
					recentPayouts={ recentPayouts }
					selectedCurrency={ selectedCurrency || undefined }
				/>
			</div>
		</div>
	);
};
