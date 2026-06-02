/// <reference path="./actions.d.ts" />
/// <reference path="./reducer.d.ts" />
/// <reference path="./selectors.d.ts" />

declare module '@wordpress/editor/build-types/store' {
	export namespace storeConfig {
	    export { reducer };
	    export { selectors };
	    export { actions };
	}
	/**
	 * Store definition for the editor namespace.
	 *
	 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
	 */
	export const store: import("@wordpress/data/build-types/types").StoreDescriptor<import("@wordpress/data/build-types/types").ReduxStoreConfig<any, typeof actions, typeof selectors>>;
	import reducer from '@wordpress/editor/build-types/store/reducer';
	import * as selectors from '@wordpress/editor/build-types/store/selectors';
	import * as actions from '@wordpress/editor/build-types/store/actions';
}
