/**
 * Internal dependencies
 */
import { isObject } from '../types/type-guards';

export const isSiteEditorPage = ( store: unknown ): boolean => {
	if ( isObject( store ) ) {
		const editedPostType = (
			store as {
				getEditedPostType: () => string;
			}
		 ).getEditedPostType();

		return (
			editedPostType === 'wp_template' ||
			editedPostType === 'wp_template_part'
		);
	}

	return false;
};
