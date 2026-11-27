/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import type { SelectFunction } from '@wordpress/data/build-types/types';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';

/**
 * Returns the template groups around the email content: at each level from the
 * root down to the post content block, every sibling group of that path. Only
 * groups qualify, as core only does this for template parts: their own content
 * can't be edited, while a block like the site title would become editable.
 */
export function getTemplateAreaClientIds( select: SelectFunction ): string[] {
	const { getBlocksByName, getBlockName, getBlockOrder, getBlockParents } =
		select( blockEditorStore );
	const [ postContentId ] = getBlocksByName( 'core/post-content' );

	if ( ! postContentId ) {
		return [];
	}

	const path = [ ...getBlockParents( postContentId ), postContentId ];

	return path.flatMap( ( clientId, index ) =>
		getBlockOrder( index === 0 ? '' : path[ index - 1 ] ).filter(
			( siblingId ) =>
				siblingId !== clientId &&
				getBlockName( siblingId ) === 'core/group'
		)
	);
}

export function isTemplateAreaEditingEnabled(
	select: SelectFunction
): boolean {
	const { getRenderingMode, getCurrentPostType, getEditorSettings } =
		select( editorStore );
	const editorSettings = getEditorSettings() as {
		onNavigateToEntityRecord?: unknown;
	};

	return (
		getRenderingMode() === 'template-locked' &&
		getCurrentPostType() !== 'wp_template' &&
		!! editorSettings?.onNavigateToEntityRecord &&
		!! select( storeName ).canUserEditTemplates() &&
		!! select( storeName ).getCurrentTemplate()?.id
	);
}

export function useIsTemplateArea( clientId: string ): boolean {
	return useSelect(
		( select ) =>
			isTemplateAreaEditingEnabled( select ) &&
			getTemplateAreaClientIds( select ).includes( clientId ),
		[ clientId ]
	);
}
