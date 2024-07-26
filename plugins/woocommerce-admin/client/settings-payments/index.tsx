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

export const SettingsPaymentsMainWrapper: React.FC = () => {
	return (
		<Suspense fallback={ null }>
			<SettingsPaymentsMainChunk />
		</Suspense>
	);
};

export const SettingsPaymentsOfflineWrapper: React.FC = () => {
	return (
		<Suspense fallback={ null }>
			<SettingsPaymentsOfflineChunk />
		</Suspense>
	);
};
