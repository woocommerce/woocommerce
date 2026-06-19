/**
 * External dependencies
 */
import { lazy, Suspense, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WooPaymentsAccountSettings } from '~/woopayments/settings/account-settings';
import {
	getWooPaymentsDepositsOverview,
	getWooPaymentsOverviewDisputes,
	getWooPaymentsOverviewShell,
	getWooPaymentsRecentDeposits,
	submitWooPaymentsInstantDeposit,
} from './data';
import { AccountBalancesCard } from './components/account-balances-card';
import { PayoutsOverviewCard } from './components/payouts-overview-card';
import { AccountDetailsCard } from './components/account-details-card';
import { ActiveLoanSummaryCard } from './components/active-loan-summary-card';
import { DisputeReadinessCard } from './components/dispute-readiness-card';
import type {
	WooPaymentsDeposit,
	WooPaymentsDepositsOverview,
	WooPaymentsOverviewDispute,
	WooPaymentsOverviewShell,
} from './types';
import { getSelectedBalanceCurrency } from './utils';
import { SpotlightPromotion } from '../../promotions/spotlight';
import { OverviewNotices } from './components/overview-notices';
import { buildOverviewTasks } from './components/overview-tasks';
import { OverviewTaskList } from './components/overview-task-list';
import { UpdateBusinessDetailsModal } from './components/update-business-details-modal';
import { ConnectionSuccessModal } from './components/connection-success-modal';

const InboxNotifications = lazy( () =>
	import( './components/inbox-notifications' ).then( ( module ) => ( {
		default: module.InboxNotifications,
	} ) )
);

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
	const [ shell, setShell ] = useState< WooPaymentsOverviewShell | null >(
		null
	);
	const [ disputes, setDisputes ] = useState< WooPaymentsOverviewDispute[] >(
		[]
	);
	const [ updateBusinessDetailsShell, setUpdateBusinessDetailsShell ] =
		useState< WooPaymentsOverviewShell | null >( null );

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

	useEffect( () => {
		let isMounted = true;

		getWooPaymentsOverviewShell()
			.then( ( nextShell ) => {
				if ( ! isMounted ) {
					return;
				}

				setShell( nextShell );

				if (
					! nextShell.account.connected ||
					nextShell.disputes_awaiting_response_count === 0
				) {
					setDisputes( [] );
					return;
				}

				getWooPaymentsOverviewDisputes()
					.then( ( response ) => {
						if ( isMounted ) {
							setDisputes( response.data ?? [] );
						}
					} )
					.catch( () => {
						if ( isMounted ) {
							setDisputes( [] );
						}
					} );
			} )
			.catch( () => {
				if ( isMounted ) {
					setShell( null );
					setDisputes( [] );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	const tasks = shell
		? buildOverviewTasks( {
				shell,
				disputes,
				onOpenUpdateBusinessDetails: setUpdateBusinessDetailsShell,
				onActivatePayments: () =>
					document.dispatchEvent(
						new CustomEvent( 'wcpay:activate_payments' )
					),
		  } )
		: [];
	const shouldShowConnectionSuccessModal =
		!! shell &&
		new URLSearchParams( window.location.search ).get(
			'wcpay-connection-success'
		) === '1' &&
		! shell.account.test_mode_onboarding &&
		shell.account.can_process_payments &&
		shell.account_status.deposits_enabled;
	const hasWorkingConnectedAccount =
		!! shell && shell.account.connected && shell.account.working;
	const shouldLoadDisputeReadiness =
		hasWorkingConnectedAccount &&
		!! shell?.feature_flags?.dispute_readiness_overview;

	return (
		<div className="woocommerce-woopayments-overview__content">
			<h1 className="woocommerce-woopayments-overview__title">
				{ __( 'Overview', 'woocommerce' ) }
			</h1>
			<OverviewNotices />
			{ shell && (
				<OverviewTaskList
					tasks={ tasks }
					visibility={ shell.overview_tasks_visibility }
				/>
			) }
			{ updateBusinessDetailsShell && (
				<UpdateBusinessDetailsModal
					shell={ updateBusinessDetailsShell }
					onClose={ () => setUpdateBusinessDetailsShell( null ) }
				/>
			) }
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
			{ shell && (
				<>
					<AccountDetailsCard
						accountDetails={ shell.account_details }
						accountFees={ shell.account_fees }
					/>
					<DisputeReadinessCard
						enabled={ shouldLoadDisputeReadiness }
						focusAfterDismissId="woocommerce-woopayments-balance-heading"
					/>
					<ActiveLoanSummaryCard
						hasActiveLoan={
							!! shell.account_loans?.has_active_loan
						}
					/>
				</>
			) }
			{ hasWorkingConnectedAccount && (
				<Suspense fallback={ null }>
					<InboxNotifications />
				</Suspense>
			) }
			{ shouldShowConnectionSuccessModal && shell && (
				<ConnectionSuccessModal
					isDismissed={ shell.is_connection_success_modal_dismissed }
				/>
			) }
			<WooPaymentsAccountSettings headingLevel={ 2 } />
			<SpotlightPromotion />
		</div>
	);
};
