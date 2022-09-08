/**
 * External dependencies
 */
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useRef } from 'react';

/**
 * Internal dependencies
 */
import { useBlockSelection } from '../hooks/block-selection';
import { getBlockContent } from './block-content';

type Selection = {
	attributeKey: string;
	clientId: string;
	offset: number;
};

interface CursorPosition {
	selectionStart: Selection;
	selectionEnd: Selection;
	hasMultiSelection: boolean;
}

/**
 * This is a simple swapping function that lets you easily swap one block for another.
 */
const swapBlocksFn =
	( name: string, extraAttributes?: Record< string, unknown > ) =>
	( blocks: BlockInstance[] ) =>
		blocks.map( ( block ) => {
			const transformed = {
				...block,
				content: getBlockContent( block ),
				...( extraAttributes || {} ),
			};
			return createBlock( name, transformed );
		} );

export const useCursor = () => {
	const {
		getSelectionStart,
		getSelectionEnd,
		hasMultiSelection: _hasMultiSelection,
	} = useSelect( blockEditorStore );
	const { selectionChange, multiSelect } = useDispatch( 'core/block-editor' );
	const cursorRef = useRef< CursorPosition >();

	const saveCursorPosition = () => {
		const selectionStart = getSelectionStart();
		const selectionEnd = getSelectionEnd();
		const hasMultiSelection = _hasMultiSelection();
		cursorRef.current = { selectionStart, selectionEnd, hasMultiSelection };

		return { selectionStart, selectionEnd, hasMultiSelection };
	};

	const selectConvertedBlocks = ( convertedBlocks: BlockInstance[] ) => {
		multiSelect(
			convertedBlocks[ 0 ].clientId,
			convertedBlocks[ convertedBlocks.length - 1 ].clientId
		);
	};

	const selectCursorAgain = ( convertedBlocks: BlockInstance[] ) => {
		if ( cursorRef.current ) {
			const { selectionStart, selectionEnd, hasMultiSelection } =
				cursorRef.current;
			if ( ! hasMultiSelection ) {
				// default of replacing a block will put cursor at the front
				// of a block. We have to do this to actually preserve the
				// selection after it actually changes.
				selectionChange(
					convertedBlocks[ 0 ].clientId,
					selectionEnd.attributeKey,
					selectionEnd.clientId === selectionStart.clientId
						? selectionStart.offset
						: selectionEnd.offset,
					selectionEnd.offset
				);
			} else {
				selectConvertedBlocks( convertedBlocks );
			}
		} else {
			throw new Error(
				'Please saveCursorPosition before selectCursorAgain'
			);
		}
	};

	return {
		saveCursorPosition,
		selectCursorAgain,
		selectConvertedBlocks,
	};
};

/**
 * Replaces the selected blocks while preserving the selection both single line and multi-line.
 */
export const useReplaceSelectedBlocks = () => {
	const { blocks, blockClientIds } = useBlockSelection();
	const { replaceBlocks } = useDispatch( 'core/block-editor' );
	const { saveCursorPosition, selectCursorAgain } = useCursor();

	async function replaceSelectedBlocks(
		transformer: ( blocks: BlockInstance[] ) => BlockInstance[]
	) {
		const convertedBlocks: BlockInstance[] = transformer( blocks );
		saveCursorPosition();
		await replaceBlocks( blockClientIds, convertedBlocks );
		selectCursorAgain( convertedBlocks );
	}

	return {
		swapBlocksFn,
		replaceSelectedBlocks,
		blocks,
		blockClientIds,
	};
};
