/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { store as coreDataStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import {
	useShortcut,
	store as keyboardShortcutsStore,
} from '@wordpress/keyboard-shortcuts';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';

// See:
//    https://github.com/WordPress/gutenberg/blob/9601a33e30ba41bac98579c8d822af63dd961488/packages/edit-post/src/components/keyboard-shortcuts/index.js
//    https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/keyboard-shortcuts/index.js

export function KeyboardShortcuts(): null {
	const { undo: undoAction, redo: redoAction } = useDispatch( coreDataStore );
	const { isSidebarOpened, hasEdits, isSaving } = useSelect( ( select ) => ( {
		isSidebarOpened: select( storeName ).isSidebarOpened(),
		isSaving: select( storeName ).isSaving(),
		hasEdits: select( storeName ).hasEdits(),
	} ) );

	const { openSidebar, closeSidebar, saveEditedEmail, toggleFeature } =
		useDispatch( storeName );

	const { registerShortcut } = useDispatch( keyboardShortcutsStore );

	useEffect( () => {
		void registerShortcut( {
			name: 'woocommerce/email-editor/toggle-fullscreen',
			category: 'global',
			description: __( 'Toggle fullscreen mode.', 'woocommerce' ),
			keyCombination: {
				modifier: 'secondary',
				character: 'f',
			},
		} );

		void registerShortcut( {
			name: 'woocommerce/email-editor/toggle-sidebar',
			category: 'global',
			description: __(
				'Show or hide the settings sidebar.',
				'woocommerce'
			),
			keyCombination: {
				modifier: 'primaryShift',
				character: ',',
			},
		} );

		void registerShortcut( {
			name: 'woocommerce/email-editor/save',
			category: 'global',
			description: __( 'Save your changes.', 'woocommerce' ),
			keyCombination: {
				modifier: 'primary',
				character: 's',
			},
		} );

		void registerShortcut( {
			name: 'woocommerce/email-editor/undo',
			category: 'block',
			description: __( 'Undo your last changes.', 'woocommerce' ),
			keyCombination: {
				modifier: 'primary',
				character: 'z',
			},
		} );

		void registerShortcut( {
			name: 'woocommerce/email-editor/redo',
			category: 'block',
			description: __( 'Redo your last undo.', 'woocommerce' ),
			keyCombination: {
				modifier: 'primaryShift',
				character: 'z',
			},
		} );
	}, [ registerShortcut ] );

	useShortcut( 'woocommerce/email-editor/toggle-fullscreen', () => {
		recordEvent( 'keyboard_shortcuts_toggle_fullscreen' );
		void toggleFeature( 'fullscreenMode' );
	} );

	useShortcut( 'woocommerce/email-editor/toggle-sidebar', ( event ) => {
		recordEvent( 'keyboard_shortcuts_toggle_sidebar' );
		event.preventDefault();

		if ( isSidebarOpened ) {
			void closeSidebar();
		} else {
			void openSidebar();
		}
	} );

	useShortcut( 'woocommerce/email-editor/save', ( event ) => {
		recordEvent( 'keyboard_shortcuts_save' );
		event.preventDefault();
		if ( ! hasEdits || isSaving ) {
			return;
		}
		void saveEditedEmail();
	} );

	useShortcut(
		// Shortcut name
		'woocommerce/email-editor/undo',
		// Shortcut callback
		( event ): void => {
			void undoAction();
			event.preventDefault();
		}
	);

	useShortcut(
		// Shortcut name
		'woocommerce/email-editor/redo',
		// Shortcut callback
		( event ): void => {
			void redoAction();
			event.preventDefault();
		}
	);

	return null;
}
