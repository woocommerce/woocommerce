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
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/interface';

/**
 * Internal dependencies
 */
import { BackButton } from './back-button';
import { EditorCanvas } from './editor-canvas';
import { EditorContext } from './context';
import { HeaderToolbar } from './header-toolbar/header-toolbar';
import { RegisterStores } from './RegisterStores';
import { ResizableEditor } from './resizable-editor';
import { SecondarySidebar } from './secondary-sidebar/secondary-sidebar';
import { SettingsSidebar } from './sidebar/settings-sidebar';
import { useEditorHistory } from './hooks/use-editor-history';
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { getGutenbergVersion } from '../../utils/get-gutenberg-version';
import { SIDEBAR_COMPLEMENTARY_AREA_SCOPE } from './constants';
import {
	KeyboardShortcuts,
	RegisterKeyboardShortcuts,
} from './keyboard-shortcuts';
import { areBlocksEmpty } from './utils/are-blocks-empty';

type IframeEditorProps = {
	initialBlocks?: BlockInstance[];
	onChange?: ( blocks: BlockInstance[] ) => void;
	onClose?: () => void;
	onInput?: ( blocks: BlockInstance[] ) => void;
	settings?: Partial< EditorSettings & EditorBlockListSettings > | undefined;
	showBackButton?: boolean;
	name: string;
};

export function IframeEditor( {
	onChange = () => {},
	onClose,
	onInput = () => {},
	settings: __settings,
	showBackButton = false,
	name,
}: IframeEditorProps ) {
	const [ resizeObserver ] = useResizeObserver();
	const [ temporalBlocks, setTemporalBlocks ] = useState< BlockInstance[] >(
		[]
	);

	// Pick the blocks from the store.
	const blocks: BlockInstance[] = useSelect( ( select ) => {
		return select( productEditorUiStore ).getModalEditorBlocks();
	}, [] );

	const { setModalEditorBlocks: setBlocks, setModalEditorContentHasChanged } =
		useDispatch( productEditorUiStore );

	const {
		appendEdit: appendToEditorHistory,
		hasRedo,
		hasUndo,
		redo,
		undo,
	} = useEditorHistory( {
		setBlocks: setTemporalBlocks,
	} );

	/*
	 * Set the initial blocks from the store.
	 * @todo: probably we can get rid of the initialBlocks prop.
	 */
	useEffect( () => {
		appendToEditorHistory( blocks );
		setTemporalBlocks( blocks );
	}, [] ); // eslint-disable-line

	const [ isInserterOpened, setIsInserterOpened ] = useState( false );
	const [ isListViewOpened, setIsListViewOpened ] = useState( false );
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This action exists in the block editor store.
	const { clearSelectedBlock, updateSettings } =
		useDispatch( blockEditorStore );

	const parentEditorSettings = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( blockEditorStore ).getSettings();
	}, [] );

	const { hasFixedToolbar } = useSelect( ( select ) => {
		// @ts-expect-error These selectors are available in the block data store.
		const { get: getPreference } = select( preferencesStore );

		return {
			hasFixedToolbar: getPreference( 'core', 'fixedToolbar' ),
		};
	}, [] );

	useEffect( () => {
		// Manually update the settings so that __unstableResolvedAssets gets added to the data store.
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		updateSettings( productBlockEditorSettings );
	}, [] );

	const handleBlockEditorProviderOnChange = (
		updatedBlocks: BlockInstance[]
	) => {
		appendToEditorHistory( updatedBlocks );
		setTemporalBlocks( updatedBlocks );
		onChange( updatedBlocks );
	};

	const handleBlockEditorProviderOnInput = (
		updatedBlocks: BlockInstance[]
	) => {
		appendToEditorHistory( updatedBlocks );
		setTemporalBlocks( updatedBlocks );
		onInput( updatedBlocks );
	};

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
				} }
			>
				<BlockEditorProvider
					settings={ {
						...settings,
						hasFixedToolbar:
							hasFixedToolbar || ! inlineFixedBlockToolbar,
						templateLock: false,
					} }
					value={ temporalBlocks }
					onChange={ handleBlockEditorProviderOnChange }
					onInput={ handleBlockEditorProviderOnInput }
					useSubRegistry={ true }
				>
					<RegisterStores />

					<KeyboardShortcuts />
					<RegisterKeyboardShortcuts />

					<HeaderToolbar
						onSave={ () => {
							setBlocks(
								areBlocksEmpty( temporalBlocks )
									? []
									: temporalBlocks
							);
							setModalEditorContentHasChanged( true );
							onChange( temporalBlocks );
							onClose?.();
						} }
						onCancel={ () => {
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
						<ComplementaryArea.Slot
							scope={ SIDEBAR_COMPLEMENTARY_AREA_SCOPE }
						/>
					</div>
					{ /* @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated. */ }
					<PluginArea scope="woocommerce-product-editor-modal-block-editor" />
					<SettingsSidebar smallScreenTitle={ name } />
				</BlockEditorProvider>
			</EditorContext.Provider>
		</div>
	);
}
