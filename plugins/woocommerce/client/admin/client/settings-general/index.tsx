/**
 * External dependencies
 */
import { Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SettingsGeneralMain } from './settings-general-main';

/**
 * Wrapper component for General Settings.
 * Provides Suspense boundary for lazy loading.
 */
export const SettingsGeneralMainWrapper = () => {
	return (
		<Suspense fallback={ <div>Loading...</div> }>
			<SettingsGeneralMain />
		</Suspense>
	);
};
