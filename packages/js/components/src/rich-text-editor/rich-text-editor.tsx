/**
 * External dependencies
 */
import { BlockEditorProvider } from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';
import { SlotFillProvider } from '@wordpress/components';
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
import { FixedFormattingToolbar } from './components/fixed-formatting-toolbar';
import { registerFormatTypes } from './formats/register-format-types';
import { registerBlocks } from './utils/register-blocks';

registerBlocks();
registerFormatTypes();

type RichTextEditorProps = {
	blocks: BlockInstance[];
	onChange: ( changes: BlockInstance[] ) => void;
	entryId?: string;
};

export const RichTextEditor: React.VFC< RichTextEditorProps > = ( {
	blocks,
	onChange,
} ) => {
	const blocksRef = useRef( blocks );

	const [ , setRefresh ] = useState( 0 );

	useEffect( () => {
		blocksRef.current = blocks;
		// If there is a props change we need to update the ref and force re-render.
		// Note: Because this component is memoized and because we don't re-render
		// when this component initiates a change, a prop change won't force the re-render
		// you'd expect. A change to the blocks must come from outside the editor.
		setRefresh( ( r ) => r + 1 );
	}, [ blocks ] );

	// Use a combo of memoization and debounce to refresh every 200 milliseconds,
	// ensuring that history is kept up to date for undo.
	const debouncedRefresh = useCallback(
		debounce( () => setRefresh( ( refresh ) => ( refresh += 1 ) ), 200 ),
		[]
	);

	const debounceChange = debounce( ( updatedBlocks ) => {
		onChange( updatedBlocks );
		blocksRef.current = updatedBlocks;
	}, 200 );

	return (
		<SlotFillProvider>
			<BlockEditorProvider
				value={ blocksRef.current }
				settings={ {
					bodyPlaceholder: '',
					hasFixedToolbar: true,
				} }
				onInput={ ( updatedBlocks ) => {
					debounceChange( updatedBlocks );

					debouncedRefresh();
				} }
				onChange={ ( updatedBlocks ) => {
					debounceChange( updatedBlocks );

					debouncedRefresh();
				} }
			>
				<FixedFormattingToolbar />

				{ /* Shortcut provider produces a div we need to style */ }
				<ShortcutProvider style={ { height: '100%' } }>
					<EditorWritingFlow />
				</ShortcutProvider>
			</BlockEditorProvider>
		</SlotFillProvider>
	);
};
