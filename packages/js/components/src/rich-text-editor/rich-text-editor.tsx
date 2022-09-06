/**
 * External dependencies
 */
import { BlockEditorProvider } from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';
import { Popover, Slot, SlotFillProvider } from '@wordpress/components';
// @ts-ignore - no types for this exist yet.
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { debounce } from 'lodash';
import {
	createElement,
	useCallback,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import React from 'react';

// import '@/styles/gutenberg.css';

/**
 * Internal dependencies
 */
import { EditorWritingFlow } from './editor-writing-flow';
import { ContextToolbar } from './components/context-toolbar';
import { FixedFormattingToolbar } from './components/fixed-formatting-toolbar';
// import { registerCompleters } from './autocomplete';
// import { LINKUI_SLOT_NAME } from './formats/link/link-ui';
// import { registerFormatTypes } from './formats/register-format-types';
// import { SLOT_NAME } from './utils/editor-popover-slot-name';
// import { registerBlocks } from './utils/register-blocks';
// import { Timetravel } from './utils/time-travel';

// registerBlocks();
// registerCompleters();
// registerFormatTypes();

type RichTextEditorProps = {
	blocks: BlockInstance[];
	onChange: ( changes: BlockInstance[] ) => void;
	entryId?: string;
};

export const RichTextEditor: React.VFC< RichTextEditorProps > = ( {
	blocks,
	onChange,
	entryId,
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
			{ /* <Slot name={ LINKUI_SLOT_NAME } /> */ }
			{ /* @ts-ignore */ }
			{ /* <Popover.__unstableSlotNameProvider value={ SLOT_NAME }> */ }
			{ /* <Popover.Slot /> */ }
			{ /* @ts-ignore */ }
			{ /* </Popover.__unstableSlotNameProvider> */ }
			<BlockEditorProvider
				value={ blocksRef.current }
				settings={ { bodyPlaceholder: '', hasFixedToolbar: true } }
				onInput={ ( updatedBlocks ) => {
					debounceChange( updatedBlocks );

					debouncedRefresh();
				} }
				onChange={ ( updatedBlocks ) => {
					debounceChange( updatedBlocks );

					debouncedRefresh();
				} }
			>
				{ /* <Slot name={ CONTEXT_TOOLBAR_SLOT_NAME } /> */ }
				{ /* Provide a separate named slot for the context menu. 
          It must be within BLockEditorProvider for useSelect hook to work */ }
				<FixedFormattingToolbar />
				<ContextToolbar />

				{ /* Shortcut provider produces a div we need to style */ }
				<ShortcutProvider style={ { height: '100%' } }>
					<EditorWritingFlow />
					{ /* <Timetravel
						blocks={ blocksRef.current }
						entryId={ entryId }
					/> */ }
				</ShortcutProvider>
			</BlockEditorProvider>
		</SlotFillProvider>
	);
};
