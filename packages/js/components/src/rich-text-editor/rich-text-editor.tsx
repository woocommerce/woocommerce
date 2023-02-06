/**
 * External dependencies
 */
import { BaseControl, Popover, SlotFillProvider } from '@wordpress/components';
import { BlockEditorProvider } from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';
import { createElement, useEffect, useState, useRef } from '@wordpress/element';
import { debounce } from 'lodash';
import React from 'react';
import { uploadMedia } from '@wordpress/media-utils';
import { useUser } from '@woocommerce/data';
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
	placeholder?: string;
};

export const RichTextEditor: React.VFC< RichTextEditorProps > = ( {
	blocks,
	label,
	onChange,
	placeholder = '',
} ) => {
	const blocksRef = useRef( blocks );
	const { currentUserCan } = useUser();
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

	const mediaUpload = currentUserCan( 'upload_files' )
		? ( {
				onError,
				...rest
		  }: {
				onError: ( message: string ) => void;
		  } ) => {
				uploadMedia(
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore The upload function passes the remaining required props.
					{
						onError: ( { message } ) => onError( message ),
						...rest,
					}
				);
		  }
		: undefined;

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
						mediaUpload,
					} }
					onInput={ debounceChange }
					onChange={ debounceChange }
				>
					<ShortcutProvider>
						<EditorWritingFlow
							blocks={ blocksRef.current }
							onChange={ onChange }
							placeholder={ placeholder }
						/>
					</ShortcutProvider>
					<Popover.Slot />
				</BlockEditorProvider>
			</SlotFillProvider>
		</div>
	);
};
