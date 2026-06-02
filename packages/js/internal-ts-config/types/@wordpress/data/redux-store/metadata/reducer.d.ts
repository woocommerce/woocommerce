/// <reference path="./actions.d.ts" />

declare module '@wordpress/data/build-types/redux-store/metadata/reducer' {
	/**
	 * External dependencies
	 */
	import EquivalentKeyMap from 'equivalent-key-map';
	type Action = ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').startResolution> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').finishResolution> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').failResolution> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').startResolutions> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').finishResolutions> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').failResolutions> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').invalidateResolution> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').invalidateResolutionForStore> | ReturnType<typeof import('@wordpress/data/build-types/redux-store/metadata/actions').invalidateResolutionForStoreSelector>;
	type StateKey = unknown[] | unknown;
	export type StateValue = {
	    status: 'resolving' | 'finished';
	} | {
	    status: 'error';
	    error: Error | unknown;
	};
	export type Status = StateValue['status'];
	export type State = EquivalentKeyMap<StateKey, StateValue>;
	/**
	 * Reducer function returning next state for selector resolution, object form:
	 *
	 *   selectorName -> EquivalentKeyMap<Array, boolean>
	 *
	 * @param state  Current state.
	 * @param action Dispatched action.
	 *
	 * @return Next state.
	 */
	declare const isResolved: (state: Record<string, State> | undefined, action: Action) => Record<string, State>;
	export default isResolved;
}
