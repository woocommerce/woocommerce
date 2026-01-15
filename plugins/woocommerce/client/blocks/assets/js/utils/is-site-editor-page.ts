/**
 * External dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CORE_EDITOR_STORE } from './wordpress-stores';

export const isSiteEditorPage = (): boolean => {
	const editor = select( CORE_EDITOR_STORE );
	const editedPostType = editor?.getCurrentPostType?.();

	return (
		editedPostType === 'wp_template' ||
		editedPostType === 'wp_template_part'
	);
};
