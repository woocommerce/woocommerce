/**
 * External dependencies
 */
import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import {
	StrictMode,
	createRoot,
	useEffect,
	useState,
	useMemo,
} from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { store as editorStore } from '@wordpress/editor';
import { useMergeRefs } from '@wordpress/compose';
import '@wordpress/format-library'; // Enables text formatting capabilities

/**
 * Internal dependencies
 */
import { getAllowedBlockNames, initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { InnerEditor } from './components/block-editor';
import { createStore, storeName } from './store';
import { initTextHooks } from './text-hooks';
import {
	initEventCollector,
	initStoreTracking,
	initDomTracking,
} from './events';
import { initContentValidationMiddleware } from './middleware/content-validation';
import {
	useContentValidation,
	useRemoveSavingFailedNotices,
	useFilterEditorContentStylesheets,
} from './hooks';
import { cleanupConfigurationChanges } from './config-tools';
import { getEditorConfigFromWindow } from './store/settings';
import { EmailEditorConfig } from './store/types';

function Editor( {
	postId,
	postType,
	isPreview = false,
	contentRef = null,
}: {
	postId: number | string;
	postType: string;
	isPreview?: boolean;
	contentRef?: React.Ref< HTMLDivElement > | null;
} ) {
	const [ isInitialized, setIsInitialized ] = useState( false );
	const { settings } = useSelect(
		( sel ) => ( {
			settings: sel( storeName ).getInitialEditorSettings(),
		} ),
		[]
	);

	useContentValidation();
	useRemoveSavingFailedNotices();

	const { setEmailPost } = useDispatch( storeName );
	useEffect( () => {
		setEmailPost( postId, postType );
		setIsInitialized( true );
	}, [ postId, postType, setEmailPost ] );

	const stylesContentRef = useFilterEditorContentStylesheets();
	const mergedContentRef = useMergeRefs( [ stylesContentRef, contentRef ] );

	// Set allowed blockTypes and isPreviewMode to the editor settings.
	const editorSettings = useMemo(
		() => ( {
			...settings,
			allowedBlockTypes: getAllowedBlockNames(),
			isPreviewMode: isPreview,
		} ),
		[ settings, isPreview ]
	);

	if ( ! isInitialized ) {
		return null;
	}

	return (
		<StrictMode>
			<InnerEditor
				postId={ postId }
				postType={ postType }
				settings={ editorSettings }
				contentRef={ mergedContentRef }
			/>
		</StrictMode>
	);
}

function onInit() {
	initEventCollector();
	initStoreTracking();
	initDomTracking();
	createStore();
	initContentValidationMiddleware();
	initBlocks();
	initTextHooks();
	initializeLayout();
}

export function initialize( elementId: string ) {
	const container = document.getElementById( elementId );
	if ( ! container ) {
		return;
	}
	const { current_post_id, current_post_type } =
		window.WooCommerceEmailEditor;

	if ( current_post_id === undefined || current_post_id === null ) {
		throw new Error( 'current_post_id is required but not provided.' );
	}

	if ( ! current_post_type ) {
		throw new Error( 'current_post_type is required but not provided.' );
	}

	const WrappedEditor = applyFilters(
		'woocommerce_email_editor_wrap_editor_component',
		Editor
	) as typeof Editor;

	onInit();

	// Set configuration to store from window object for backward compatibility
	const editorConfig = getEditorConfigFromWindow();
	dispatch( storeName ).setEditorConfig( editorConfig );

	const root = createRoot( container );
	root.render(
		<WrappedEditor
			postId={ current_post_id }
			postType={ current_post_type }
		/>
	);
}

export function ExperimentalEmailEditor( {
	postId,
	postType,
	isPreview = false,
	contentRef = null,
	config,
}: {
	postId: string;
	postType: string;
	isPreview?: boolean;
	contentRef?: React.Ref< HTMLDivElement > | null;
	config?: EmailEditorConfig;
} ) {
	const [ isInitialized, setIsInitialized ] = useState( false );

	useEffect( () => {
		const backupEditorSettings = select( editorStore ).getEditorSettings();
		// Set configuration to store from window object for backward compatibility
		const editorConfig = config || getEditorConfigFromWindow();
		onInit();

		dispatch( storeName ).setEditorConfig( editorConfig );
		setIsInitialized( true );
		// Cleanup global editor settings
		return () => {
			try {
				cleanupConfigurationChanges();
			} finally {
				dispatch( editorStore ).updateEditorSettings(
					backupEditorSettings
				);
			}
		};
	}, [ config ] );

	const WrappedEditor = applyFilters(
		'woocommerce_email_editor_wrap_editor_component',
		Editor
	) as typeof Editor;

	if ( ! isInitialized ) {
		return null;
	}

	return (
		<WrappedEditor
			postId={ postId }
			postType={ postType }
			isPreview={ isPreview }
			contentRef={ contentRef }
		/>
	);
}
