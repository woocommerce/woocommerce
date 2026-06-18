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
} from './data';
import { AccountBalancesCard } from './components/account-balances-card';
import { PayoutsOverviewCard } from './components/payouts-overview-card';
import type { WooPaymentsDeposit, WooPaymentsDepositsOverview } from './types';
import { getDefaultCurrency } from './utils';

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
	const [ isLoading, setIsLoading ] = useState( true );
	const [ overviewErrorMessage, setOverviewErrorMessage ] = useState<
		string | null
	>( null );
	const [ payoutsErrorMessage, setPayoutsErrorMessage ] = useState<
		string | null
	>( null );

	useEffect( () => {
		let isMounted = true;

		const loadOverview = async () => {
			setIsLoading( true );

			const loadRecentPayouts = async ( currency: string ) => {
				try {
					const recent = await getWooPaymentsRecentDeposits(
						currency
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
				}
			};

			try {
				const nextOverview = await getWooPaymentsDepositsOverview();
				const currency = getDefaultCurrency( nextOverview );

				if ( ! isMounted ) {
					return;
				}

				setOverview( nextOverview );
				setOverviewErrorMessage( null );

				await loadRecentPayouts( currency );
			} catch ( error ) {
				if ( isMounted ) {
					setOverview( null );
					setOverviewErrorMessage( getErrorMessage( error ) );
				}

				await loadRecentPayouts( '' );
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

	return (
		<div className="woocommerce-woopayments-overview__content">
			<WooPaymentsAccountSettings />
			<div className="woocommerce-woopayments-overview__cards">
				<AccountBalancesCard
					isLoading={ isLoading }
					errorMessage={ overviewErrorMessage }
					overview={ overview }
				/>
				<PayoutsOverviewCard
					isLoading={ isLoading }
					errorMessage={ payoutsErrorMessage }
					overview={ overview }
					recentPayouts={ recentPayouts }
				/>
			</div>
		</div>
	);
};
