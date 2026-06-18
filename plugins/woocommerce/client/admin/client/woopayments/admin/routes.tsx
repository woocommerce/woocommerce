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

registerSettingsPaymentsProviderRoute( {
	id: 'woopayments-overview',
	path: '/woopayments/overview',
	order: 100,
	element: (
		<Suspense
			fallback={
				<div>
					{ sprintf(
						/* translators: %s: WooPayments */
						__( 'Loading %s…', 'woocommerce' ),
						'WooPayments'
					) }
				</div>
			}
		>
			<WooPaymentsOverviewChunk />
		</Suspense>
	),
} );
