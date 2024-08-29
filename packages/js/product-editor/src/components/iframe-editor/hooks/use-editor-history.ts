/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useDebounce } from '@wordpress/compose';
import { useCallback, useState } from '@wordpress/element';

type useEditorHistoryProps = {
	maxHistory?: number;
	setBlocks: ( blocks: BlockInstance[] ) => void;
};

const DEFAULT_MAX_HISTORY = 50;

export function useEditorHistory( {
	maxHistory = DEFAULT_MAX_HISTORY,
	setBlocks,
}: useEditorHistoryProps ) {
	const [ edits, setEdits ] = useState< BlockInstance[][] >( [] );
	const [ offsetIndex, setOffsetIndex ] = useState< number >( 0 );

	const appendEdit = useDebounce(
		useCallback(
			( edit: BlockInstance[] ) => {
				const currentEdits = edits.slice( 0, offsetIndex + 1 );
				const newEdits = [ ...currentEdits, edit ].slice(
					maxHistory * -1
				);
				setEdits( newEdits );
				setOffsetIndex( newEdits.length - 1 );
			},
			[ edits, maxHistory, offsetIndex ]
		),
		500
	);

	const undo = useCallback( () => {
		appendEdit.flush();

		const newIndex = Math.max( 0, offsetIndex - 1 );
		if ( ! edits[ newIndex ] ) {
			return;
		}
		setBlocks( edits[ newIndex ] );
		setOffsetIndex( newIndex );
	}, [ appendEdit, edits, offsetIndex, setBlocks ] );

	const redo = useCallback( () => {
		appendEdit.flush();

		const newIndex = Math.min( edits.length - 1, offsetIndex + 1 );
		if ( ! edits[ newIndex ] ) {
			return;
		}
		setBlocks( edits[ newIndex ] );
		setOffsetIndex( newIndex );
	}, [ appendEdit, edits, offsetIndex, setBlocks ] );

	function hasUndo() {
		return !! edits.length && offsetIndex > 0;
	}

	function hasRedo() {
		return !! edits.length && offsetIndex < edits.length - 1;
	}

	return {
		appendEdit,
		hasRedo: hasRedo(),
		hasUndo: hasUndo(),
		redo,
		undo,
	};
}
