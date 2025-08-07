/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../store';
import { EmailTemplate } from '../store/types';

/**
 * Hook to detect if we are currently in the email editor context.
 *
 * This hook checks:
 * 1. If the email editor store is present
 * 2. If the currently edited post matches the email editor store's post ID and type
 * 3. If editing a template, checks if it's associated with the email editor store's post
 *
 * @return {boolean} True if we are in the email editor context, false otherwise
 */
export function useIsEmailEditor(): boolean {
	return useSelect( ( select ) => {
		// First, check if the email editor store exists
		const emailEditorStore = select( storeName );
		if ( ! emailEditorStore ) {
			return false;
		}

		// Get the email editor store's post information
		const emailPostId = emailEditorStore.getEmailPostId();
		const emailPostType = emailEditorStore.getEmailPostType();

		// Get the current post information from the WordPress editor
		const currentPostId = select( editorStore ).getCurrentPostId();
		const currentPostType = select( editorStore ).getCurrentPostType();

		// Check if the current post matches the email editor post
		const currentPostMatch =
			String( currentPostId ) === String( emailPostId ) &&
			String( currentPostType ) === String( emailPostType );

		// If the current post matches the email editor post, we are in the email editor context
		if ( currentPostMatch ) {
			return true;
		}

		// If we're editing a template, check if it's associated with the email editor post
		if ( currentPostType === 'wp_template' ) {
			// If we're editing a template, check if it's associated with the email editor post
			// Get the current template being edited
			const currentTemplate = emailEditorStore.getCurrentTemplate();
			if ( ! currentTemplate ) {
				return false;
			}

			// Check if this template is associated with the email editor post
			// We need to check if the template is used by the email post type
			const emailTemplates =
				emailEditorStore.getEmailTemplates() as EmailTemplate[];
			if ( ! emailTemplates ) {
				return false;
			}

			// Check if the current template is in the list of email templates
			// and if it's associated with the email post type
			const isEmailTemplate = emailTemplates.some( ( template ) => {
				return (
					template.id === currentTemplate.id &&
					template.post_types?.includes( emailPostType )
				);
			} );
			return isEmailTemplate;
		}
		return false;
	}, [] );
}
