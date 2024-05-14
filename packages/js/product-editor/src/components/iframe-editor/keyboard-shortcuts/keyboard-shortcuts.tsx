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
	const {
		isDocumentOverviewOpened: isListViewOpened,
		redo,
		setIsDocumentOverviewOpened: setIsListViewOpened,
		undo,
	} = useContext( EditorContext );

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

	useShortcut(
		'woocommerce/product-editor/modal-block-editor/toggle-list-view',
		( event ) => {
			setIsListViewOpened( ! isListViewOpened );
			event.preventDefault();
		}
	);

	return null;
};
