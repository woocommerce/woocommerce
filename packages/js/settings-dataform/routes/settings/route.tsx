/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect } from '@wordpress/data';
import '../../packages/settings-ui/fields/product';

/**
 * Internal dependencies
 */
import { SETTINGS_ENTITY, SETTINGS_ARGS } from './constants';
import { stage } from './stage';

export const route = {
	beforeLoad: async () => {
		await dispatch( coreStore ).addEntities( [ SETTINGS_ENTITY ] );
	},
	loader: async () => {
		const { getEntityRecord } = resolveSelect( coreStore );

		// Let the stage handle request errors and missing form configuration.
		await Promise.allSettled( [ getEntityRecord( ...SETTINGS_ARGS ) ] );
	},
	stage,
};
