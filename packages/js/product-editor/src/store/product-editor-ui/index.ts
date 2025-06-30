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

const store = 'woo/product-editor-ui';

export const wooProductEditorUiStore = createReduxStore( store, {
	actions,
	selectors,
	reducer,
} );

export default function registerProductEditorUiStore() {
	register( wooProductEditorUiStore );
}
