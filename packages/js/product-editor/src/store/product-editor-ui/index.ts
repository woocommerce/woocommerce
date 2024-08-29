/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import actions from './actions';
import selectors from './selectors';
import reducer from './reducer';
/**
 * Types
 */

export const store = 'woo/product-editor-ui';

const wooProductEditorUiStore = createReduxStore( store, {
	actions,
	selectors,
	reducer,
} );

export default function registerProductEditorUiStore() {
	register( wooProductEditorUiStore );
}
