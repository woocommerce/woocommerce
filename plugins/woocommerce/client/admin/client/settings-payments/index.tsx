/**
 * External dependencies
 */
import { lazy, Suspense } from '@wordpress/element';

const SettingsPaymentsMainChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-main" */ './settings-payments-main'
		)
);

const SettingsPaymentsOfflineChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-offline" */ './settings-payments-offline'
		)
);

const SettingsPaymentsWooCommercePaymentsChunk = lazy(
	() =>
		import(
			/* webpackChunkName: "settings-payments-woocommerce-payments" */ './settings-payments-woocommerce-payments'
		)
);

export const SettingsPaymentsMainWrapper: React.FC = () => {
	return (
		<Suspense fallback={ <div>Loading main settings...</div> }>
			<SettingsPaymentsMainChunk />
		</Suspense>
	);
};

export const SettingsPaymentsOfflineWrapper: React.FC = () => {
	return (
		<Suspense fallback={ <div>Loading offline settings...</div> }>
			<SettingsPaymentsOfflineChunk />
		</Suspense>
	);
};

export const SettingsPaymentsWooCommercePaymentsWrapper: React.FC = () => {
	return (
		<Suspense
			fallback={ <div>Loading WooCommerce Payments settings...</div> }
		>
			<SettingsPaymentsWooCommercePaymentsChunk />
		</Suspense>
	);
};
