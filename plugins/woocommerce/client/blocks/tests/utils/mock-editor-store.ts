/**
 * Jest mock factory for @wordpress/data that registers a mock core/editor store.
 *
 * This is needed because we use string-based store selectors ('core/editor')
 * instead of importing the store from @wordpress/editor (which would add
 * wp-editor as a script dependency). The import side-effect would normally
 * register the store, but since we avoid the import, we need to register
 * a mock in tests.
 *
 * Usage (must use require, not import, due to Jest hoisting):
 * ```
 * jest.mock( '@wordpress/data', () =>
 *     require( '@woocommerce/blocks-test-utils/mock-editor-store' ).mockWordPressDataWithEditorStore()
 * );
 * ```
 */
export const mockWordPressDataWithEditorStore = () => {
	// Use require to avoid issues with Jest's module system
	// eslint-disable-next-line @typescript-eslint/no-var-requires
	const wpData = require( 'wordpress-data-wp-6-7' );
	const mockEditorStore = wpData.createReduxStore( 'core/editor', {
		reducer: () => ( {} ),
		selectors: {
			getCurrentPostId: () => null,
			getCurrentPostType: () => null,
			getCurrentPost: () => null,
			isCurrentPostPublished: () => false,
		},
	} );
	wpData.register( mockEditorStore );
	return {
		__esModule: true,
		...wpData,
	};
};
