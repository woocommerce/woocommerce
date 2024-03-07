/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';

const setPreferencesPersistence = () =>
	dispatch( preferencesStore ).setPersistenceLayer( {
		get: async () => {
			const savedPreferences = window.localStorage.getItem(
				'woo-ai-plugin-prefs'
			);
			return savedPreferences ? JSON.parse( savedPreferences ) : {};
		},
		set: ( preferences: object ) => {
			window.localStorage.setItem(
				'woo-ai-plugin-prefs',
				JSON.stringify( preferences )
			);
		},
	} );

export default setPreferencesPersistence;
