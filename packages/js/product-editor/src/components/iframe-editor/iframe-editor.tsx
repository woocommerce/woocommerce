/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { Popover } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement, useEffect, useState } from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import { PluginArea } from '@wordpress/plugins';
import classNames from 'classnames';
import { isWpVersion } from '@woocommerce/settings';
import {
	store as preferencesStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/preferences';
// eslint-disable-next-line @woocommerce/dependency-group
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
// eslint-disable-next-line @woocommerce/dependency-group
import {
	ComplementaryArea,
	store as interfaceStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/interface';

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
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { getGutenbergVersion } from '../../utils/get-gutenberg-version';

type IframeEditorProps = {
	initialBlocks?: BlockInstance[];
	onChange?: ( blocks: BlockInstance[] ) => void;
	onClose?: () => void;
	onInput?: ( blocks: BlockInstance[] ) => void;
	settings?: Partial< EditorSettings & EditorBlockListSettings > | undefined;
	showBackButton?: boolean;
};

export function IframeEditor( {
	onChange = () => {},
	onClose,
	onInput = () => {},
	settings: __settings,
	showBackButton = false,
}: IframeEditorProps ) {
	const [ resizeObserver ] = useResizeObserver();
	const [ temporalBlocks, setTemporalBlocks ] = useState< BlockInstance[] >(
		[]
	);

	// Pick the blocks from the store.
	const blocks: BlockInstance[] = useSelect( ( select ) => {
		return select( productEditorUiStore ).getModalEditorBlocks();
	}, [] );

	/*
	 * Set the initial blocks from the store.
	 * @todo: probably we can get rid of the initialBlocks prop.
	 */
	useEffect( () => {
		setTemporalBlocks( blocks );
	}, [] ); // eslint-disable-line

	const { setModalEditorBlocks: setBlocks, setModalEditorContentHasChanged } =
		useDispatch( productEditorUiStore );

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

	const { hasFixedToolbar, isRightSidebarOpen } = useSelect( ( select ) => {
		// @ts-expect-error These selectors are available in the block data store.
		const { get: getPreference } = select( preferencesStore );

		// @ts-expect-error These selectors are available in the interface data store.
		const { getActiveComplementaryArea } = select( interfaceStore );

		return {
			hasFixedToolbar: getPreference( 'core', 'fixedToolbar' ),
			isRightSidebarOpen: getActiveComplementaryArea(
				'woocommerce/product-editor-iframe-editor'
			),
		};
	}, [] );

	console.log( 'isRightSidebarOpen', isRightSidebarOpen );

	useEffect( () => {
		// Manually update the settings so that __unstableResolvedAssets gets added to the data store.
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		updateSettings( productBlockEditorSettings );
	}, [] );

	const settings = __settings || parentEditorSettings;

	const inlineFixedBlockToolbar =
		isWpVersion( '6.5', '>=' ) || getGutenbergVersion() > 17.3;

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
						hasFixedToolbar:
							hasFixedToolbar || ! inlineFixedBlockToolbar,
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
							setModalEditorContentHasChanged( true );
							onChange( temporalBlocks );
							onClose?.();
						} }
						onCancel={ () => {
							appendEdit( blocks );
							setBlocks( blocks );
							onChange( blocks );
							setTemporalBlocks( blocks );
							onClose?.();
						} }
					/>
					<div className="woocommerce-iframe-editor__main">
						<SecondarySidebar />
						<BlockTools
							className={ classNames(
								'woocommerce-iframe-editor__content',
								{
									'old-fixed-toolbar-shown':
										! inlineFixedBlockToolbar,
								}
							) }
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
							{ showBackButton && onClose && (
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
								{ isRightSidebarOpen && (
									<ComplementaryArea.Slot scope="woocommerce/product-editor-iframe-editor" />
								) }
							</div>
						) }
					</div>
					{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
					<PluginArea scope="woocommerce-product-editor-iframe-editor" />
				</BlockEditorProvider>
			</EditorContext.Provider>
		</div>
	);
}
