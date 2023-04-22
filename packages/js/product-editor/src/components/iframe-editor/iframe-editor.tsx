/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useEffect, useState } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import {
	BlockEditorProvider,
	BlockList,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	BlockTools,
	BlockEditorKeyboardShortcuts,
	EditorSettings,
	EditorBlockListSettings,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	store as blockEditorStore,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { EditorCanvas } from './editor-canvas';
import { ResizableEditor } from './resizable-editor';

type IframeEditorProps = {
	settings?: Partial< EditorSettings & EditorBlockListSettings > | undefined;
};

export function IframeEditor( { settings }: IframeEditorProps ) {
	const [ resizeObserver, sizes ] = useResizeObserver();
	const [ blocks, setBlocks ] = useState< BlockInstance[] >( [] );
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This action exists in the block editor store.
	const { clearSelectedBlock, updateSettings } =
		useDispatch( blockEditorStore );

	const parentEditorSettings = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( blockEditorStore ).getSettings();
	}, [] );

	useEffect( () => {
		// Manually update the settings so that __unstableResolvedAssets gets added to the data store.
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		updateSettings( productBlockEditorSettings );
	}, [] );

	return (
		<BlockEditorProvider
			settings={ {
				...( settings || parentEditorSettings ),
				templateLock: false,
			} }
			value={ blocks }
			onChange={ setBlocks }
			useSubRegistry={ true }
		>
			<BlockTools
				className={ 'woocommerce-iframe-editor' }
				onClick={ (
					event: React.MouseEvent< HTMLDivElement, MouseEvent >
				) => {
					// Clear selected block when clicking on the gray background.
					if ( event.target === event.currentTarget ) {
						clearSelectedBlock();
					}
				} }
			>
				{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
				{ /* @ts-ignore */ }
				<BlockEditorKeyboardShortcuts.Register />
				<ResizableEditor
					enableResizing={ true }
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore This accepts numbers or strings.
					height={ sizes.height ?? '100%' }
				>
					<EditorCanvas enableResizing={ true }>
						{ resizeObserver }
						<BlockList className="edit-site-block-editor__block-list wp-site-blocks" />
					</EditorCanvas>
				</ResizableEditor>
			</BlockTools>
		</BlockEditorProvider>
	);
}
