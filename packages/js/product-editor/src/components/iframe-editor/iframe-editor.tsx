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
	closeModal?: () => void;
	initialBlocks?: BlockInstance[];
	onChange?: ( blocks: BlockInstance[] ) => void;
	onClose?: () => void;
	onInput?: ( blocks: BlockInstance[] ) => void;
	settings?: Partial< EditorSettings & EditorBlockListSettings > | undefined;
};

export function IframeEditor( {
	closeModal = () => {},
	initialBlocks = [],
	onChange = () => {},
	onClose,
	onInput = () => {},
	settings: __settings,
}: IframeEditorProps ) {
	const [ resizeObserver ] = useResizeObserver();
	const [ blocks, setBlocks ] = useState< BlockInstance[] >( initialBlocks );
	const [ temporalBlocks, setTemporalBlocks ] =
		useState< BlockInstance[] >( initialBlocks );
	const { appendEdit } = useEditorHistory( {
		setBlocks,
	} );
	const {
		appendEdit: tempAppendEdit,
		hasRedo,
		hasUndo,
		redo,
		undo,
	} = useEditorHistory( {
		setBlocks: setTemporalBlocks,
	} );
	const [ isInserterOpened, setIsInserterOpened ] = useState( false );
	const [ isListViewOpened, setIsListViewOpened ] = useState( false );
	const [ isSidebarOpened, setIsSidebarOpened ] = useState( true );
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
					isDocumentOverviewOpened: isListViewOpened,
					redo,
					setIsInserterOpened,
					setIsDocumentOverviewOpened: setIsListViewOpened,
					undo,
					isSidebarOpened,
					setIsSidebarOpened,
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
						tempAppendEdit( updatedBlocks );
						setTemporalBlocks( updatedBlocks );
						onChange( updatedBlocks );
					} }
					onInput={ ( updatedBlocks: BlockInstance[] ) => {
						tempAppendEdit( updatedBlocks );
						setTemporalBlocks( updatedBlocks );
						onInput( updatedBlocks );
					} }
					useSubRegistry={ true }
				>
					<HeaderToolbar
						onSave={ () => {
							appendEdit( temporalBlocks );
							setBlocks( temporalBlocks );
							onChange( temporalBlocks );
							closeModal();
						} }
						onCancel={ () => {
							appendEdit( blocks );
							setBlocks( blocks );
							onChange( blocks );
							setTemporalBlocks( blocks );
							closeModal();
						} }
					/>
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
								height="100%"
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
							{ /* This is a hack, but I couldn't find another (easy) way to not
							     have the inserter render in the content's padding. I believe
								 that is happening because the inserter is positioned using a transforms,
								 which take it outside of the normal layout, thus ignoring the parent's
								 bounds. */ }
							<div className="woocommerce-iframe-editor__content-inserter-clipper" />
						</BlockTools>
						{ isSidebarOpened && (
							<div className="woocommerce-iframe-editor__sidebar">
								<BlockInspector />
							</div>
						) }
					</div>
				</BlockEditorProvider>
			</EditorContext.Provider>
		</div>
	);
}
