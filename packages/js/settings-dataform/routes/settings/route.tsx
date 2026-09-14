/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect } from '@wordpress/data';
import {
	registerProductFields,
	SETTINGS_ENTITY,
	SETTINGS_ARGS,
} from '@woocommerce-settings-ui-experimental/settings-ui';

export const route = {
	beforeLoad: async () => {
		registerProductFields();
		await dispatch( coreStore ).addEntities( [ SETTINGS_ENTITY ] );
	},
	loader: async () => {
		const { getEntityRecord } = resolveSelect( coreStore );

		// Let the stage handle request errors and missing form configuration.
		await Promise.allSettled( [ getEntityRecord( ...SETTINGS_ARGS ) ] );
	},
};
