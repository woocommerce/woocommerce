/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useInstanceId } from '@wordpress/compose';
import { createElement, useEffect } from '@wordpress/element';
import { createBlock } from '@wordpress/blocks';
import {
	BlockList,
	ObserveTyping,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	store as blockEditorStore,
	WritingFlow,
} from '@wordpress/block-editor';

export const EditorWritingFlow: React.VFC = () => {
	const instanceId = useInstanceId( EditorWritingFlow );
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This action is available in the block editor data store.
	const { insertBlock } = useDispatch( blockEditorStore );

	const { isEmpty } = useSelect( ( select ) => {
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
	} );

	useEffect( () => {
		if ( isEmpty ) {
			const initialBlock = createBlock( 'core/paragraph', {
				content: '',
			} );
			insertBlock( initialBlock );
		}
	}, [] );

	return (
		/* Gutenberg handles the keyboard events when focusing the content editable area. */
		/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
		<div
			className="woocommerce-rich-text-editor__writing-flow"
			id={ `woocommerce-rich-text-editor__writing-flow-${ instanceId }` }
			style={ {
				cursor: isEmpty ? 'text' : 'initial',
			} }
		>
			<BlockTools>
				<WritingFlow>
					<ObserveTyping>
						{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
						{ /* @ts-ignore This action is available in the block editor data store. */ }
						<BlockList />
					</ObserveTyping>
				</WritingFlow>
			</BlockTools>
		</div>
		/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
	);
};
