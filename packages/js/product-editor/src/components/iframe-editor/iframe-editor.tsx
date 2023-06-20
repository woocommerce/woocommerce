/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { Popover } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useEffect, useState } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import {
	BlockEditorProvider,
	BlockInspector,
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
import { BackButton } from './back-button';
import { EditorCanvas } from './editor-canvas';
import { EditorContext } from './context';
import { HeaderToolbar } from './header-toolbar/header-toolbar';
import { ResizableEditor } from './resizable-editor';
import { SecondarySidebar } from './secondary-sidebar/secondary-sidebar';
import { useEditorHistory } from './hooks/use-editor-history';

type IframeEditorProps = {
	initialBlocks?: BlockInstance[];
	onChange?: ( blocks: BlockInstance[] ) => void;
	onClose?: () => void;
	onInput?: ( blocks: BlockInstance[] ) => void;
	settings?: Partial< EditorSettings & EditorBlockListSettings > | undefined;
};

export function IframeEditor( {
	initialBlocks = [],
	onChange = () => {},
	onClose,
	onInput,
	settings: __settings,
}: IframeEditorProps ) {
	const [ resizeObserver, sizes ] = useResizeObserver();
	const [ blocks, setBlocks ] = useState< BlockInstance[] >( initialBlocks );
	const { appendEdit, hasRedo, hasUndo, redo, undo } = useEditorHistory( {
		setBlocks,
	} );
	const [ isInserterOpened, setIsInserterOpened ] = useState( false );
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

	const settings = __settings || parentEditorSettings;

	return (
		<div className="woocommerce-iframe-editor">
			<EditorContext.Provider
				value={ {
					hasRedo,
					hasUndo,
					isInserterOpened,
					redo,
					setIsInserterOpened,
					undo,
				} }
			>
				<BlockEditorProvider
					settings={ {
						...settings,
						hasFixedToolbar: true,
						templateLock: false,
					} }
					value={ blocks }
					onChange={ ( updatedBlocks: BlockInstance[] ) => {
						appendEdit( updatedBlocks );
						setBlocks( updatedBlocks );
						onChange( updatedBlocks );
					} }
					onInput={ onInput }
					useSubRegistry={ true }
				>
					<HeaderToolbar />
					<div className="woocommerce-iframe-editor__main">
						<SecondarySidebar />
						<BlockTools
							className={ 'woocommerce-iframe-editor__content' }
							onClick={ (
								event: React.MouseEvent<
									HTMLDivElement,
									MouseEvent
								>
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
							{ onClose && (
								<BackButton
									onClick={ () => {
										setTimeout( onClose, 550 );
									} }
								/>
							) }
							<ResizableEditor
								enableResizing={ true }
								// eslint-disable-next-line @typescript-eslint/ban-ts-comment
								// @ts-ignore This accepts numbers or strings.
								height={ sizes.height ?? '100%' }
							>
								<EditorCanvas
									enableResizing={ true }
									settings={ settings }
								>
									{ resizeObserver }
									<BlockList className="edit-site-block-editor__block-list wp-site-blocks" />
								</EditorCanvas>
								<Popover.Slot />
							</ResizableEditor>
						</BlockTools>
						<div className="woocommerce-iframe-editor__sidebar">
							<BlockInspector />
						</div>
					</div>
				</BlockEditorProvider>
			</EditorContext.Provider>
		</div>
	);
}
