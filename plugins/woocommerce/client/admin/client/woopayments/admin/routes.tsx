/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerSettingsPaymentsProviderRoute } from '~/settings-payments/provider-routes';

const WooPaymentsSettingsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-settings" */ '../settings'
		)
);

const WooPaymentsExpressCheckoutSettingsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-express-checkout-settings" */ '../settings/express-checkout'
		)
);

const WooPaymentsOverviewChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-overview" */ './overview'
		)
);

const WooPaymentsPayoutsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-payouts" */ './payouts'
		)
);

const WooPaymentsPayoutDetailsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-payouts" */ './payout-details'
		)
);

const WooPaymentsTransactionsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-money-movement" */ './money-movement/transactions'
		)
);

const WooPaymentsTransactionDetailsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-money-movement" */ './money-movement/transaction-details'
		)
);

const WooPaymentsDisputesChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-money-movement" */ './money-movement/disputes'
		)
);

const WooPaymentsDisputeDetailsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-money-movement" */ './money-movement/disputes-details'
		)
);

const WooPaymentsDisputeChallengeChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-money-movement" */ './money-movement/dispute-challenge'
		)
);

const WooPaymentsCardReadersChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-card-readers" */ './card-readers'
		)
);

const WooPaymentsCapitalChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woopayments-capital" */ './capital'
		)
);

const LoadingFallback = () => (
	<div role="status" aria-live="polite" aria-busy="true">
		{ sprintf(
			/* translators: %s: WooPayments */
			__( 'Loading %s…', 'woocommerce' ),
			'WooPayments'
		) }
	</div>
);

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-settings',
	path: '/woopayments/settings',
	order: 90,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsSettingsChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-express-checkout-settings',
	path: '/woopayments/settings/express-checkout/:methodId',
	order: 91,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsExpressCheckoutSettingsChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-overview',
	path: '/woopayments/overview',
	order: 100,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsOverviewChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-payouts',
	path: '/woopayments/payouts',
	order: 110,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsPayoutsChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-payout-details',
	path: '/woopayments/payouts/details',
	order: 111,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsPayoutDetailsChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-transactions',
	path: '/woopayments/transactions',
	order: 120,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsTransactionsChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-transaction-details',
	path: '/woopayments/transactions/details',
	order: 121,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsTransactionDetailsChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-disputes',
	path: '/woopayments/disputes',
	order: 122,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsDisputesChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-dispute-details',
	path: '/woopayments/disputes/details',
	order: 123,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsDisputeDetailsChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-dispute-challenge',
	path: '/woopayments/disputes/challenge',
	order: 124,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsDisputeChallengeChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-card-readers',
	path: '/woopayments/card-readers',
	order: 125,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsCardReadersChunk />
		</Suspense>
	),
} );

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-capital',
	path: '/woopayments/loans',
	order: 126,
	element: (
		<Suspense fallback={ <LoadingFallback /> }>
			<WooPaymentsCapitalChunk />
		</Suspense>
	),
} );
