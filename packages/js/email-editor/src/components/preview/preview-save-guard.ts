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

		// Find the triggering element by the selector and early return if not found.
		const triggerEl = target?.closest( selector );
		if ( ! triggerEl ) {
			return;
		}

		const editorStoreInstance = select( editorStore );
		const isDirty = editorStoreInstance?.isEditedPostDirty();

		if ( ! isDirty ) {
			return;
		}

		// If there are unsaved changes, prevent the default action (opening the link).
		event.preventDefault();
		event.stopPropagation();
		event.stopImmediatePropagation();

		dispatch( noticesStore ).createNotice(
			'warning',
			__(
				'You have unsaved changes. Please save the post before previewing.',
				'woocommerce'
			),
			{
				context: 'email-editor',
				isDismissible: true,
			}
		);
	};

	/**
	 * Handles keydown events for the preview button for Enter and Space keys.
	 *
	 * @param {KeyboardEvent} event The triggered event.
	 */
	const keydownHandler = useCallback(
		( event ) => {
			try {
				const target = event.target;
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

	return null;
};
