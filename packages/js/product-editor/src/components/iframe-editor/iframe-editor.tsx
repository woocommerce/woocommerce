/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { Popover } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	createElement,
	useCallback,
	useEffect,
	useReducer,
	useState,
} from '@wordpress/element';
import { useResizeObserver } from '@wordpress/compose';
import { PluginArea } from '@wordpress/plugins';
import classNames from 'classnames';
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
import { wooProductEditorUiStore } from '../../store/product-editor-ui';
import { SIDEBAR_COMPLEMENTARY_AREA_SCOPE } from './constants';
import {
	KeyboardShortcuts,
	RegisterKeyboardShortcuts,
} from './keyboard-shortcuts';
import { areBlocksEmpty } from './utils/are-blocks-empty';

type SidebarState = {
	isInserterOpened: boolean;
	isListViewOpened: boolean;
};

const setIsInserterOpenedAction = 'SET_IS_INSERTER_OPENED';
const setIsListViewOpenedAction = 'SET_IS_LISTVIEW_OPENED';
const initialSidebarState: SidebarState = {
	isInserterOpened: false,
	isListViewOpened: false,
};
function sidebarReducer(
	state: SidebarState,
	action: { type: string; value: boolean }
): SidebarState {
	switch ( action.type ) {
		case setIsInserterOpenedAction: {
			return {
				...state,
				isInserterOpened: action.value,
				isListViewOpened: action.value ? false : state.isListViewOpened,
			};
		}
		case setIsListViewOpenedAction: {
			return {
				...state,
				isListViewOpened: action.value,
				isInserterOpened: action.value ? false : state.isInserterOpened,
			};
		}
	}
	return state;
}

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
		return select( wooProductEditorUiStore ).getModalEditorBlocks();
	}, [] );

	const { setModalEditorBlocks: setBlocks, setModalEditorContentHasChanged } =
		useDispatch( wooProductEditorUiStore );

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

	const [ { isInserterOpened, isListViewOpened }, dispatch ] = useReducer(
		sidebarReducer,
		initialSidebarState
	);

	const setIsInserterOpened = useCallback( ( value: boolean ) => {
		dispatch( {
			type: setIsInserterOpenedAction,
			value,
		} );
	}, [] );

	const setIsListViewOpened = useCallback( ( value: boolean ) => {
		dispatch( {
			type: setIsListViewOpenedAction,
			value,
		} );
	}, [] );

	const { clearSelectedBlock, updateSettings } =
		useDispatch( blockEditorStore );

	const parentEditorSettings = useSelect( ( select ) => {
		// @ts-expect-error Selector is not typed
		return select( blockEditorStore ).getSettings();
	}, [] );

	const { hasFixedToolbar } = useSelect( ( select ) => {
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
						hasFixedToolbar,
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
								'woocommerce-iframe-editor__content'
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
								{ /* @ts-expect-error name does exist on PopoverSlot see: https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/popover/index.tsx#L555 */ }
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
					<PluginArea scope="woocommerce-product-editor-modal-block-editor" />
					<SettingsSidebar smallScreenTitle={ name } />
				</BlockEditorProvider>
			</EditorContext.Provider>
		</div>
	);
}
