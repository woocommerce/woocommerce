/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { createElement, Fragment, useCallback } from '@wordpress/element';
import {
	BlockList,
	ObserveTyping,
	// @ts-ignore
	BlockTools,
	store as blockEditorStore,
	WritingFlow,
} from '@wordpress/block-editor';

export const ID = 'entry-editor';

export const EditorWritingFlow: React.VFC = () => {
	// @ts-ignore
	const { resetSelection } = useDispatch( blockEditorStore );

	const { firstBlock, isEmpty } = useSelect( ( select ) => {
		const blocks = select( 'core/block-editor' ).getBlocks();

		const isEmpty = blocks.length
			? blocks.length <= 1 &&
			  blocks[ 0 ].attributes?.content?.trim() === ''
			: true;

        console.log(blocks);

		return {
			isEmpty,
			firstBlock: blocks[ 0 ],
		};
	} );

	// A combination of cursor on hover with CSS and this click handler ensures that clicking on
	// an empty editor starts you in the first paragraph and ready to type.
	const setSelectionOnClick = useCallback( () => {
		if ( isEmpty ) {
			const position = {
				offset: 0,
				clientId: firstBlock?.clientId,
				attributeKey: 'content',
			};

			resetSelection( position, position, 0 );
		}
	}, [ isEmpty, firstBlock ] );

	return (
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
	);
};
