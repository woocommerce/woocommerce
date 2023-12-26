/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

export const useEditedPostType = () => {
	// This will return 'post' or 'page' when in the post/page editor.
	const { currentPostType } = useSelect( ( select ) => {
		const store = select( 'core/editor' );
		if ( ! store ) {
			return {
				currentPostType: null,
			};
		}
		const { getCurrentPostType } = store;
		return {
			currentPostType: getCurrentPostType(),
		};
	} );
	// This will return 'wp_template' or 'wp_template_part' when in the Site Editor.
	const { editedPostType } = useSelect( ( select ) => {
		const store = select( 'core/edit-site' );
		if ( ! store ) {
			return {
				editedPostType: null,
			};
		}
		const { getEditedPostType } = store;
		return {
			editedPostType: getEditedPostType(),
		};
	} );

	return { editedPostType: editedPostType || currentPostType };
};
