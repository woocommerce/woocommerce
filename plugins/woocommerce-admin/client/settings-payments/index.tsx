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

export const SettingsPaymentsMainWrapper: React.FC = () => {
	return (
		<Suspense fallback={ null }>
			<SettingsPaymentsMainChunk />
		</Suspense>
	);
};
