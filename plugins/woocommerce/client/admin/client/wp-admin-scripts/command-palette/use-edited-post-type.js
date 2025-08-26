/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store } from '@wordpress/editor';

export const useEditedPostType = () => {
	const { editedPostType } = useSelect( ( select ) => {
		return {
			editedPostType: select( store ).getCurrentPostType(),
		};
	} );

	return { editedPostType };
};
