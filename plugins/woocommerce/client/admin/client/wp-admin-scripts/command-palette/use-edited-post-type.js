/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

export const useEditedPostType = () => {
	const { editedPostType } = useSelect( ( select ) => {
		return {
			editedPostType: select( 'core/editor' ).getCurrentPostType(),
		};
	} );

	return { editedPostType };
};
