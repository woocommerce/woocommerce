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
	const { undo } = useContext( EditorContext );

	useShortcut(
		'woocommerce/product-editor/modal-block-editor/undo',
		( event ) => {
			undo();
			event.preventDefault();
		}
	);

	return null;
};
