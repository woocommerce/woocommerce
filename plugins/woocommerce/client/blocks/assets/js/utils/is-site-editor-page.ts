/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

export const isSiteEditorPage = (): boolean => {
	// @ts-expect-error getCurrentPostType is not typed.
	const editedPostType = select( editorStore )?.getCurrentPostType();

	return (
		editedPostType === 'wp_template' ||
		editedPostType === 'wp_template_part'
	);
};
