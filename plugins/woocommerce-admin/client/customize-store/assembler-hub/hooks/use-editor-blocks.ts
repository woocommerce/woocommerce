/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { useEntityBlockEditor } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
import { useSelect } from '@wordpress/data';
import { BlockInstance } from '@wordpress/blocks';

export type ChangeHandler = (
	blocks: BlockInstance[],
	options: Record< string, unknown >
) => void;

// Note, must be used within BlockEditorProvider. This allows shared access of blocks currently
// being edited in the BlockEditor.
export const useEditorBlocks = (): [
	BlockInstance[],
	ChangeHandler,
	ChangeHandler
] => {
	const { templateType } = useSelect( ( select ) => {
		const { getEditedPostType } = unlock( select( editSiteStore ) );

		return {
			templateType: getEditedPostType(),
		};
	}, [] );

	// @ts-ignore Types are not up to date.
	const [ blocks, onInput, onChange ]: [
		BlockInstance[] | undefined,
		ChangeHandler,
		ChangeHandler
	] = useEntityBlockEditor( 'postType', templateType );

	return [ blocks ?? [], onInput, onChange ];
};
