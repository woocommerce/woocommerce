/// <reference path="./actions.d.ts" />
/// <reference path="./selectors.d.ts" />

declare module '@wordpress/notices/build-types/store' {
	/**
	 * Store definition for the notices namespace.
	 *
	 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
	 */
	export const store: import("@wordpress/data/build-types/types").StoreDescriptor<import("@wordpress/data/build-types/types").ReduxStoreConfig<any, typeof actions, typeof selectors>>;
	import * as actions from '@wordpress/notices/build-types/store/actions';
	import * as selectors from '@wordpress/notices/build-types/store/selectors';
}
