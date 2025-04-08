/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import {
	__experimentalLibrary as Library, // eslint-disable-line
	store as blockEditorStore,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useEditorMode } from '../../hooks';
import { recordEvent } from '../../events';

export function InserterSidebar() {
	const { postContentId } = useSelect( ( select ) => {
		const blocks = select( blockEditorStore ).getBlocks();
		return {
			postContentId: blocks.find(
				( block ) => block.name === 'core/post-content'
			)?.clientId,
		};
	} );

	const [ editorMode ] = useEditorMode();

	// @ts-expect-error missing types.
	const { setIsInserterOpened } = useDispatch( editorStore );

	return (
		<div className="editor-inserter-sidebar">
			<div className="editor-inserter-sidebar__content">
				<Library
					showMostUsedBlocks
					showInserterHelpPanel={ false }
					// In the email content mode we insert primarily into the post content block.
					rootClientId={
						editorMode === 'email' ? postContentId : null
					}
					onClose={ () => {
						setIsInserterOpened( false );
						recordEvent(
							'inserter_sidebar_library_close_icon_clicked',
							{
								editorMode,
							}
						);
					} }
					onSelect={ ( selectedBlock ) => {
						recordEvent(
							'inserter_sidebar_library_block_selected',
							{
								editorMode,
								blockName: selectedBlock?.name,
							}
						);
					} }
				/>
			</div>
		</div>
	);
}
