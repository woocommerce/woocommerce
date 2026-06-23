/**
 * Jest mock factory for @wordpress/data that registers a mock core/editor store.
 *
 * This is needed because we use string-based store selectors ('core/editor')
 * instead of importing the store from @wordpress/editor (which would add
 * wp-editor as a script dependency). The import side-effect would normally
 * register the store, but since we avoid the import, we need to register
 * a mock in tests.
 *
 * Usage:
 * ```
 * jest.mock( '@wordpress/data', () =>
 *     jest
 *         .requireActual( '@woocommerce/blocks-test-utils/mock-editor-store' )
 *         .mockWordPressDataWithEditorStore()
 * );
 * ```
 */
/**
 * Internal dependencies
 */
import {
	getActualWordPressData,
	mockWordPressData,
} from './mock-wordpress-data';

export const mockWordPressDataWithEditorStore = () => {
	const wpData = getActualWordPressData();
	const selectors = {
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
	};

	try {
		const registerStore = wpData.registerStore;
		if ( typeof registerStore !== 'function' ) {
			throw new Error( '@wordpress/data registerStore is unavailable.' );
		}

		wpData.registerStore( 'core/editor', {
			reducer: () => ( {} ),
			selectors,
		} );
		return mockWordPressData();
	} catch ( error ) {
		const select = ( storeNameOrDescriptor ) => {
			if (
				storeNameOrDescriptor === 'core/editor' ||
				storeNameOrDescriptor?.name === 'core/editor'
			) {
				return selectors;
			}

			return wpData.select( storeNameOrDescriptor );
		};
		const dispatch = ( storeNameOrDescriptor ) => {
			if (
				storeNameOrDescriptor === 'core/editor' ||
				storeNameOrDescriptor?.name === 'core/editor'
			) {
				return {};
			}

			return wpData.dispatch( storeNameOrDescriptor );
		};

		return mockWordPressData( {
			dispatch,
			select,
		} );
	}
};
