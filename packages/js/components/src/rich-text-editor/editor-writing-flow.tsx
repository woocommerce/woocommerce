/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { createElement, useCallback } from '@wordpress/element';
import {
	BlockList,
	ObserveTyping,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	store as blockEditorStore,
	WritingFlow,
} from '@wordpress/block-editor';

export const ID = 'entry-editor';

export const EditorWritingFlow: React.VFC = () => {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This action is available in the block editor data store.
	const { resetSelection } = useDispatch( blockEditorStore );

	const { firstBlock, isEmpty, selectedBlockClientIds } = useSelect(
		( select ) => {
			const blocks = select( 'core/block-editor' ).getBlocks();

			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore This selector is available in the block editor data store.
			const { getSelectedBlockClientIds } = select( blockEditorStore );

			return {
				isEmpty: blocks.length
					? blocks.length <= 1 &&
					  blocks[ 0 ].attributes?.content?.trim() === ''
					: true,
				firstBlock: blocks[ 0 ],
				selectedBlockClientIds: getSelectedBlockClientIds(),
			};
		}
	);

	// A combination of cursor on hover with CSS and this click handler ensures that clicking on
	// an empty editor starts you in the first paragraph and ready to type.
	const setSelectionOnClick = useCallback( () => {
		if ( isEmpty || ! selectedBlockClientIds.length ) {
			const position = {
				offset: 0,
				clientId: firstBlock?.clientId,
				attributeKey: 'content',
			};

			resetSelection( position, position, 0 );
		}
	}, [ isEmpty, firstBlock, selectedBlockClientIds ] );

	return (
		/* Gutenberg handles the keyboard events when focusing the content editable area. */
		/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
		<div
			id={ ID }
			style={ {
				display: 'flex',
				height: '100%',
				cursor: isEmpty ? 'text' : 'initial',
				padding: 6,
			} }
			onClick={ setSelectionOnClick }
		>
			<BlockTools>
				<WritingFlow>
					<ObserveTyping>
						<BlockList />
					</ObserveTyping>
				</WritingFlow>
			</BlockTools>
		</div>
		/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
	);
};
