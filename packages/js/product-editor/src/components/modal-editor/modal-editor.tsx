/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { createElement, useState } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import {
	BlockList,
	// @ts-ignore
	BlockTools,
	BlockEditorKeyboardShortcuts,
	// @ts-ignore
	privateApis as blockEditorPrivateApis,
	// @ts-ignore
	// unlock,
	store as blockEditorStore,
} from '@wordpress/block-editor';
// @ts-ignore
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

/**
 * Internal dependencies
 */
import { EditorCanvas } from './editor-canvas';
import { ResizableEditor } from './resizable-editor';
// import BackButton from './back-button';

const { unlock } = __dangerousOptInToUnstableAPIsOnlyForCoreModules(
	'I know using unstable features means my plugin or theme will inevitably break on the next WordPress release.',
	'@wordpress/block-editor'
);

const { ExperimentalBlockEditorProvider } = unlock( blockEditorPrivateApis );

export function ModalEditor() {
	const [ resizeObserver, sizes ] = useResizeObserver();
	const [ blocks, setBlocks ] = useState< BlockInstance[] >( [] );
	// @ts-ignore This action exists in the block editor store.
	const { clearSelectedBlock } = useDispatch( blockEditorStore );

	return (
		<ExperimentalBlockEditorProvider
			// @ts-ignore
			settings={ productBlockEditorSettings }
			value={ blocks }
			// onInput={ onInput }
			onChange={ setBlocks }
			useSubRegistry={ true }
		>
			<BlockTools
				className={ 'woocommerce-modal-editor' }
				// __unstableContentRef={ contentRef }
				onClick={ (
					event: React.MouseEvent< HTMLDivElement, MouseEvent >
				) => {
					// Clear selected block when clicking on the gray background.
					if ( event.target === event.currentTarget ) {
						clearSelectedBlock();
					}
				} }
			>
				{ /* @ts-ignore */ }
				<BlockEditorKeyboardShortcuts.Register />
				{ /* <BackButton /> */ }
				<ResizableEditor
					enableResizing={ true }
					height={ sizes.height ?? '100%' }
				>
					<EditorCanvas enableResizing={ true } settings={ {} }>
						{ resizeObserver }
						<BlockList className="edit-site-block-editor__block-list wp-site-blocks" />
					</EditorCanvas>
				</ResizableEditor>
			</BlockTools>
		</ExperimentalBlockEditorProvider>
	);
}
