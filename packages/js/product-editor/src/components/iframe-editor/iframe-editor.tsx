/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { createElement, useState } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import {
	BlockList,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	BlockTools,
	BlockEditorKeyboardShortcuts,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	privateApis as blockEditorPrivateApis,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	store as blockEditorStore,
} from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
// eslint-disable-next-line @woocommerce/dependency-group
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

/**
 * Internal dependencies
 */
import { EditorCanvas } from './editor-canvas';
import { ResizableEditor } from './resizable-editor';

const { unlock } = __dangerousOptInToUnstableAPIsOnlyForCoreModules(
	'I know using unstable features means my plugin or theme will inevitably break on the next WordPress release.',
	'@wordpress/block-editor'
);

const { ExperimentalBlockEditorProvider } = unlock( blockEditorPrivateApis );

export function IframeEditor() {
	const [ resizeObserver, sizes ] = useResizeObserver();
	const [ blocks, setBlocks ] = useState< BlockInstance[] >( [] );
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This action exists in the block editor store.
	const { clearSelectedBlock } = useDispatch( blockEditorStore );
	return (
		<ExperimentalBlockEditorProvider
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			settings={ productBlockEditorSettings }
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
		</ExperimentalBlockEditorProvider>
	);
}
