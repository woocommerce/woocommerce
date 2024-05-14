/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';

export const RegisterKeyboardShortcuts = () => {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore registerShortcut is not defined in the types
	const { registerShortcut } = useDispatch( keyboardShortcutsStore );

	useEffect( () => {
		registerShortcut( {
			name: 'woocommerce/product-editor/modal-block-editor/undo',
			category: 'global',
			description: __( 'Undo your last changes.', 'woocommerce' ),
			keyCombination: {
				modifier: 'primary',
				character: 'z',
			},
		} );
	}, [ registerShortcut ] );

	return null;
};
