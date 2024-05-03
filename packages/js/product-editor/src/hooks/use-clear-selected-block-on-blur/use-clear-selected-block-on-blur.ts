/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

// This is a workaround to hide the toolbar when the block is blurred.
// This is a temporary solution until using Gutenberg 18 with the
// fix from https://github.com/WordPress/gutenberg/pull/59800
export const useClearSelectedBlockOnBlur = () => {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	const { clearSelectedBlock } = useDispatch( blockEditorStore );

	function handleBlur( event: {
		relatedTarget: ( EventTarget & Element ) | null;
	} ) {
		const isToolbarOrLinkPopover =
			event?.relatedTarget?.closest(
				'.block-editor-block-contextual-toolbar'
			) || event?.relatedTarget?.closest( '.block-editor-link-control' );

		if ( ! isToolbarOrLinkPopover ) {
			clearSelectedBlock();
		}
	}

	return {
		handleBlur,
	};
};
