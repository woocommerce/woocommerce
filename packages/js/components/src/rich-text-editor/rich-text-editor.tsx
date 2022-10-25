/**
 * External dependencies
 */
import { BaseControl, SlotFillProvider } from '@wordpress/components';
import { BlockEditorProvider } from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';
import { debounce } from 'lodash';
import {
	createElement,
	useCallback,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import React from 'react';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';

/**
 * Internal dependencies
 */
import { EditorWritingFlow } from './editor-writing-flow';
import { registerBlocks } from './utils/register-blocks';

registerBlocks();

type RichTextEditorProps = {
	blocks: BlockInstance[];
	label?: string;
	onChange: ( changes: BlockInstance[] ) => void;
	entryId?: string;
};

export const RichTextEditor: React.VFC< RichTextEditorProps > = ( {
	blocks,
	label,
	onChange,
} ) => {
	const blocksRef = useRef( blocks );

	const [ , setRefresh ] = useState( 0 );

	// If there is a props change we need to update the ref and force re-render.
	// Note: Because this component is memoized and because we don't re-render
	// when this component initiates a change, a prop change won't force the re-render
	// you'd expect. A change to the blocks must come from outside the editor.
	const forceRerender = () => {
		setRefresh( ( refresh ) => refresh + 1 );
	};

	useEffect( () => {
		blocksRef.current = blocks;
		forceRerender();
	}, [ blocks ] );

	const debounceChange = debounce( ( updatedBlocks ) => {
		onChange( updatedBlocks );
		blocksRef.current = updatedBlocks;
		forceRerender();
	}, 200 );

	return (
		<div className="woocommerce-rich-text-editor">
			{ label && (
				<BaseControl.VisualLabel>{ label }</BaseControl.VisualLabel>
			) }
			<SlotFillProvider>
				<BlockEditorProvider
					value={ blocksRef.current }
					settings={ {
						bodyPlaceholder: '',
						hasFixedToolbar: true,
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						// @ts-ignore This property was recently added in the block editor data store.
						__experimentalClearBlockSelection: false,
					} }
					onInput={ debounceChange }
					onChange={ debounceChange }
				>
					<ShortcutProvider>
						<EditorWritingFlow />
					</ShortcutProvider>
				</BlockEditorProvider>
			</SlotFillProvider>
		</div>
	);
};
