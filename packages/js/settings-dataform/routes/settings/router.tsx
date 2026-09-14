/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SETTINGS_ENTITY, SETTINGS_ARGS, VIEW_CONFIG_ARGS } from './constants';

export const route = {
	beforeLoad: async () => {
		await dispatch( coreStore ).addEntities( [ SETTINGS_ENTITY ] );
	},
	loader: async () => {
		const { getEntityRecords, getViewConfig } = resolveSelect( coreStore );

		// Let the stage handle request errors and missing form configuration.
		await Promise.allSettled( [
			getEntityRecords( ...SETTINGS_ARGS ),
			getViewConfig( ...VIEW_CONFIG_ARGS ),
		] );
	},
};
