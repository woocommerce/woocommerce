/**
 * External dependencies
 */
import { getPath } from '@wordpress/url';
import { select } from '@wordpress/data';

/**
 * Returns true if the current page is in the editor.
 *
 * The core/editor store can be registered on the storefront when another plugin
 * enqueues wp-editor, so its mere presence is not proof we are in the editor. A
 * real (including custom) block editor always has a post loaded for editing,
 * which the storefront never does, so fall back to checking for a current post.
 */
export const isEditor = (): boolean => {
	const path = getPath( window.location.href );

	if ( path?.includes( 'site-editor.php' ) || path?.includes( 'post.php' ) ) {
		return true;
	}

	const editorSelectors = select( 'core/editor' ) as unknown as
		| { getCurrentPostId?: () => number | null | undefined }
		| undefined;

	return !! editorSelectors?.getCurrentPostId?.();
};
