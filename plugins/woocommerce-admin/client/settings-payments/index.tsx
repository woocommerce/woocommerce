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
		<Suspense fallback={ <div>Loading main settings...</div> }>
			<ErrorBoundary fallback={ <div>Error loading main settings</div> }>
				<SettingsPaymentsMainChunk />
			</ErrorBoundary>
		</Suspense>
	);
};

export const SettingsPaymentsOfflineWrapper: React.FC = () => {
	return (
		<Suspense fallback={ <div>Loading offline settings...</div> }>
			<ErrorBoundary fallback={ <div>Error loading offline settings</div> }>
				<SettingsPaymentsOfflineChunk />
			</ErrorBoundary>
		</Suspense>
	);
};
