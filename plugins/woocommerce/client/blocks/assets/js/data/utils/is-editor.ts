/**
 * External dependencies
 */
import { getPath } from '@wordpress/url';

/**
 * Returns true if the current page is in the editor.
 */
export const isEditor = (): boolean => {
	return (
		getPath( window.location.href )?.includes( 'site-editor.php' ) ||
		getPath( window.location.href )?.includes( 'post.php' ) ||
		false
	);
};
