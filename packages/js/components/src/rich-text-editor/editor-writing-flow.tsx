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
	const { insertBlock, selectBlock, __unstableSetEditorMode } =
		useDispatch( blockEditorStore );

	const { selectedBlockClientIds, editorMode } = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore This selector is available in the block editor data store.
		const { getSelectedBlockClientIds, __unstableGetEditorMode } =
			select( blockEditorStore );
		return {
			editorMode: __unstableGetEditorMode(),
			selectedBlockClientIds: getSelectedBlockClientIds(),
		};
	}, [] );

	// This is a workaround to prevent focusing the block on initialization.
	// Changing to a mode other than "edit" ensures that no initial position
	// is found and no element gets subsequently focused.
	// See https://github.com/WordPress/gutenberg/blob/411b6eee8376e31bf9db4c15c92a80524ae38e9b/packages/block-editor/src/components/block-list/use-block-props/use-focus-first-element.js#L42
	const setEditorIsInitializing = ( isInitializing: boolean ) => {
		if ( typeof __unstableSetEditorMode !== 'function' ) {
			return;
		}

		__unstableSetEditorMode( isInitializing ? 'initialized' : 'edit' );
	};

	useEffect( () => {
		if ( selectedBlockClientIds?.length || ! firstBlock ) {
			return;
		}

		setEditorIsInitializing( true );
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

	const maybeSetEditMode = () => {
		if ( editorMode === 'edit' ) {
			return;
		}
		setEditorIsInitializing( false );
	};

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
				<WritingFlow
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore These are forwarded as props to the WritingFlow component.
					onClick={ maybeSetEditMode }
					onFocus={ maybeSetEditMode }
				>
					<ObserveTyping>
						<BlockList />
					</ObserveTyping>
				</WritingFlow>
			</BlockTools>
		</div>
		/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
	);
};
