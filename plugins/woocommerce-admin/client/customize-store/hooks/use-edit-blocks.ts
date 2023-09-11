/**
 * External dependencies
 */
import { useEntityBlockEditor } from '@wordpress/core-data';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
import { useSelect } from '@wordpress/data';
import { BlockInstance } from '@wordpress/blocks';

// Note, must be used within BlockEditorProvider. This allows shared access of blocks currently
// being edited in the BlockEditor.
export const useEditBlocks = () => {
	const { templateType } = useSelect( ( select ) => {
		const { getEditedPostType } = unlock( select( editSiteStore ) );

		return {
			templateType: getEditedPostType(),
		};
	}, [] );

	const [ blocks, onInput, onChange ]: [ BlockInstance[] ] =
		useEntityBlockEditor( 'postType', templateType );

	return [ blocks, onInput, onChange ];
};
