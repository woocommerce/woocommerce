/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import classnames from 'classnames';
import { createElement, useState } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import {
	BlockList,
	BlockInspector,
	// @ts-ignore
	BlockTools,
	BlockEditorKeyboardShortcuts,
	BlockEditorProvider,
} from '@wordpress/block-editor';
// @ts-ignore

/**
 * Internal dependencies
 */
import { EditorCanvas } from './editor-canvas';
import { ResizableEditor } from './resizable-editor';
// import { SidebarInspectorFill } from '../sidebar-edit-mode';
// import BackButton from './back-button';

// const LAYOUT = {
// 	type: 'default',
// 	// At the root level of the site editor, no alignments should be allowed.
// 	alignments: [],
// };

export function ModalEditor() {
	// const [ resizeObserver ] = useResizeObserver();
	const [ blocks, setBlocks ] = useState< BlockInstance[] >( [] );

	return (
		<BlockEditorProvider
			// settings={ settings }
			value={ blocks }
			// onInput={ onInput }
			onChange={ setBlocks }
			useSubRegistry={ true }
		>
			{ /* <TemplatePartConverter /> */ }
			{ /* <Sidebar.InspectorFill>
                <BlockInspector />
            </Sidebar.InspectorFill> */ }
			{ /* Potentially this could be a generic slot (e.g. EditorCanvas.Slot) if there are other uses for it. */ }

			<BlockTools
				className={ classnames( 'edit-site-visual-editor', {
					'is-focus-mode': true,
				} ) }
				// __unstableContentRef={ contentRef }
				// onClick={ ( event ) => {
				// 	// Clear selected block when clicking on the gray background.
				// 	if ( event.target === event.currentTarget ) {
				// 		clearSelectedBlock();
				// 	}
				// } }
			>
				{ /* <BlockEditorKeyboardShortcuts.Register /> */ }
				{ /* <BackButton /> */ }
				<ResizableEditor enableResizing={ true } height={ '100%' }>
					<EditorCanvas
						enableResizing={ true }
						settings={ {} }
						// contentRef={ mergedRefs }
						// readonly={ canvasMode === 'view' }
					>
						{ /* { resizeObserver } */ }
						<BlockList
							className="edit-site-block-editor__block-list wp-site-blocks"
							// __experimentalLayout={ LAYOUT }
							// renderAppender={ true }
						/>
					</EditorCanvas>
				</ResizableEditor>
			</BlockTools>
		</BlockEditorProvider>
	);
}
