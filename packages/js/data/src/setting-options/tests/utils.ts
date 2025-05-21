/**
 * External dependencies
 */
import { createRegistry } from '@wordpress/data';
import { controls } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../';
import * as selectors from '../selectors';
import * as actions from '../actions';
import reducer from '../reducer';
import type { SettingsState } from '../types';
import { Setting, SettingsGroup } from '../types';
// @ts-expect-error WP core data doesn't explicitly export the actions
// eslint-disable-next-line @woocommerce/dependency-group
import createLocksActions from '@wordpress/core-data/build/locks/actions';

/**
 * Creates a fresh registry and store for testing.
 */
export const createTestRegistryAndStore = () => {
	const registry = createRegistry();

	// Create initial state matching the reducer's initial state
	const initialState: SettingsState = {
		settings: {},
		groups: [],
		edits: {},
		isSaving: {
			settings: {},
			groups: {},
		},
		errors: {},
	};

	const store = registry.registerStore( STORE_NAME, {
		reducer,
		actions: {
			...actions,
			...createLocksActions(),
		},
		controls,
		selectors,
		initialState, // Pass initial state to ensure fresh state each time
	} );

	return { registry, store };
};

/**
 * Creates test setting data with the correct Setting type.
 */
export const createTestSetting = (
	overrides: Partial< Setting > = {}
): Setting => ( {
	id: 'test-setting',
	label: 'Test Setting',
	description: 'Test Description',
	type: 'text',
	value: 'test-value',
	...overrides,
} );

/**
 * Creates test setting group data with the correct SettingsGroup type.
 */
export const createTestSettingGroup = (
	overrides: Partial< SettingsGroup > = {}
): SettingsGroup => ( {
	id: 'test-group',
	label: 'Test Group',
	description: 'Test Group Description',
	parent_id: '',
	sub_groups: [],
	...overrides,
} );

/**
 * Creates a test error.
 */
export const createTestError = ( message = 'Test error' ) =>
	new Error( message );
