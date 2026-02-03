/**
 * Initialize hacks for the email editor.
 *
 * This module contains temporary workarounds and fixes for compatibility issues
 * with WordPress/Gutenberg features. These should be reviewed and potentially
 * removed when better long-term solutions are available.
 */

/**
 * Internal dependencies
 */
import { modifyTemplateActions } from './modify-template-actions';
import { modifyMoveToTrashAction } from './move-to-trash';

/**
 * Initialize all hacks.
 */
export function initHacks() {
	modifyMoveToTrashAction();
	modifyTemplateActions();
}
