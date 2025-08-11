/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';

/**
 * A component that adds a confirmation dialog for unsaved changes
 * when a user tries to open a preview in a new tab.
 */
export const PreviewSaveGuard = () => {
	const selector = '.editor-preview-dropdown__button-external';

	/**
	 * Handles click/keydown events to check for unsaved changes before previewing.
	 *
	 * @param {Event} event The triggered event.
	 */
	const guard = async ( event ) => {
		const target = event.target;

		// Find the triggering element (the preview button).
		const triggerEl = target?.closest( selector );
		if ( ! triggerEl ) {
			return;
		}

		// Check if there are any unsaved changes using the editor data store.
		const editorStoreInstance = select( editorStore );
		const isDirty = editorStoreInstance?.isEditedPostDirty?.();

		if ( ! isDirty ) {
			// If the post is saved, do nothing and let the preview open normally.
			return;
		}

		// If there are unsaved changes, prevent the default action (opening the link).
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();

		// Dispatch a notice to inform the user they need to save first.
		dispatch( noticesStore ).createNotice(
			'info',
			__(
				'You have unsaved changes. Please save the post before previewing.',
				'woocommerce'
			),
			{
				type: 'snackbar',
				isDismissible: true,
			}
		);
	};

	/**
	 * Handles keydown events for the preview button.
	 *
	 * @param {KeyboardEvent} event The triggered event.
	 */
	const keydownHandler = useCallback(
		( event ) => {
			try {
				const target = event.target;
				// Trigger on 'Enter' or 'Space' keys if the target is the preview button.
				if (
					( event.key === 'Enter' || event.key === ' ' ) &&
					target?.closest( selector )
				) {
					guard( event );
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.warn( 'Preview save message guard error:', error );
			}
		},
		[ selector ]
	);

	// The `useEffect` hook manages the lifecycle of the event listeners.
	useEffect( () => {
		// Add event listeners when the component is mounted.
		// We use the 'capture' phase to ensure our handler runs before the default React handler.
		document.addEventListener( 'click', guard, true );
		document.addEventListener( 'auxclick', guard, true );
		document.addEventListener( 'keydown', keydownHandler, true );

		// The cleanup function, which runs when the component is unmounted.
		// This ensures the listeners are properly removed, preventing memory leaks.
		return () => {
			document.removeEventListener( 'click', guard, true );
			document.removeEventListener( 'auxclick', guard, true );
			document.removeEventListener( 'keydown', keydownHandler, true );
		};
	}, [ keydownHandler ] );

	// The component doesn't need to render any UI, its functionality is purely in the `useEffect` hook.
	return null;
};
