/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';
import { useShortcut } from '@wordpress/keyboard-shortcuts';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';

export const KeyboardShortcuts = () => {
	const { redo, undo } = useContext( EditorContext );

	useShortcut(
		'woocommerce/product-editor/modal-block-editor/undo',
		( event ) => {
			undo();
			event.preventDefault();
		}
	);

	useShortcut(
		'woocommerce/product-editor/modal-block-editor/redo',
		( event ) => {
			redo();
			event.preventDefault();
		}
	);

	return null;
};
