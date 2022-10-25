/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useInstanceId } from '@wordpress/compose';
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { createElement, useEffect } from '@wordpress/element';
import {
	BlockList,
	ObserveTyping,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	store as blockEditorStore,
	WritingFlow,
} from '@wordpress/block-editor';

type EditorWritingFlowProps = {
	blocks: BlockInstance[];
	onChange: ( changes: BlockInstance[] ) => void;
	placeholder?: string;
};

export const EditorWritingFlow = ( {
	blocks,
	onChange,
	placeholder = '',
}: EditorWritingFlowProps ) => {
	const instanceId = useInstanceId( EditorWritingFlow );
	const firstBlock = blocks[ 0 ];
	const isEmpty = ! blocks.length;
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This action is available in the block editor data store.
	const { insertBlock, selectBlock } = useDispatch( blockEditorStore );
	const { selectedBlockClientIds } = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore This selector is available in the block editor data store.
		const { getSelectedBlockClientIds } = select( blockEditorStore );

		return {
			selectedBlockClientIds: getSelectedBlockClientIds(),
		};
	} );

	useEffect( () => {
		if ( selectedBlockClientIds?.length || ! firstBlock ) {
			return;
		}
		selectBlock( firstBlock.clientId );
	}, [ firstBlock, selectedBlockClientIds ] );

	useEffect( () => {
		if ( isEmpty ) {
			const initialBlock = createBlock( 'core/paragraph', {
				content: '',
				placeholder,
			} );
			insertBlock( initialBlock );
			onChange( [ initialBlock ] );
		}
	}, [ isEmpty ] );

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
						<BlockList />
					</ObserveTyping>
				</WritingFlow>
			</BlockTools>
		</div>
		/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
	);
};
