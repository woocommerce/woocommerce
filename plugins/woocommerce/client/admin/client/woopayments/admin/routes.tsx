/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerSettingsPaymentsProviderRoute } from '~/settings-payments/provider-routes';

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

const LoadingFallback = () => (
	<div>
		{ sprintf(
			/* translators: %s: WooPayments */
			__( 'Loading %s…', 'woocommerce' ),
			'WooPayments'
		) }
	</div>
);

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
