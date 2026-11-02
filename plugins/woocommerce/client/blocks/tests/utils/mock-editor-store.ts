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
	// `jest.requireActual` bypasses the mock and loads the real module,
	// avoiding the circular dependency that would occur with a plain
	// `require( '@wordpress/data' )` inside a jest.mock factory.
	const wpData = jest.requireActual( '@wordpress/data' );
	const mockEditorStore = wpData.createReduxStore( 'core/editor', {
		reducer: () => ( {} ),
		selectors: {
			getCurrentPostId: () => null,
			getCurrentPostType: () => null,
			getCurrentPost: () => null,
			isCurrentPostPublished: () => false,
			// wp-6.8: additional selectors that @wordpress/block-editor and
			// @wordpress/editor components may call during inner-block
			// rendering. Without these, inner blocks silently fail to render.
			getEditorSettings: () => ( {} ),
			getEditedPostAttribute: () => undefined,
			getEditedPostSlug: () => '',
			getEditorMode: () => 'visual',
			getRenderingMode: () => 'all',
			getPostTypeLabel: () => '',
		},
	} );
	wpData.register( mockEditorStore );
	return {
		__esModule: true,
		...wpData,
	};
};
