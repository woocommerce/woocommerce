/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	StrictMode,
	createRoot,
	useEffect,
	useLayoutEffect,
	useState,
} from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import '@wordpress/format-library'; // Enables text formatting capabilities

/**
 * Internal dependencies
 */
import { getAllowedBlockNames, initBlocks } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { InnerEditor } from './components/block-editor';
import { createStore, storeName } from './store';
import { initHooks } from './editor-hooks';
import { initTextHooks } from './text-hooks';
import {
	initEventCollector,
	initStoreTracking,
	initDomTracking,
} from './events';
import { useContentValidation } from './hooks/use-content-validation';

function Editor( {
	postId,
	postType,
	isPreview = false,
}: {
	postId: string;
	postType: string;
	isPreview?: boolean;
} ) {
	const [ isInitialized, setIsInitialized ] = useState( false );
	const { settings } = useSelect(
		( select ) => ( {
			settings: select( storeName ).getInitialEditorSettings(),
		} ),
		[]
	);

	useContentValidation();

	const { setEmailPost } = useDispatch( storeName );
	useEffect( () => {
		setEmailPost( postId, postType );
		setIsInitialized( true );
	}, [ postId, postType ] );

	if ( ! isInitialized ) {
		return null;
	}

	// Set allowed blockTypes to the editor settings.
	settings.allowedBlockTypes = getAllowedBlockNames();
	settings.isPreviewMode = isPreview;

	return (
		<StrictMode>
			<InnerEditor
				postId={ postId }
				postType={ postType }
				settings={ settings }
			/>
		</StrictMode>
	);
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
	initEventCollector();
	initStoreTracking();
	initDomTracking();
	createStore();
	initializeLayout();
	initBlocks();
	initHooks();
	initTextHooks();
	const root = createRoot( container );
	root.render(
		<WrappedEditor
			postId={ current_post_id }
			postType={ current_post_type }
		/>
	);
}

export function EmailEditor( {
	postId,
	postType,
	isPreview = false,
}: {
	postId: string;
	postType: string;
	isPreview?: boolean;
} ) {
	const [ isInitialized, setIsInitialized ] = useState( false );

	useLayoutEffect( () => {
		initEventCollector();
		initStoreTracking();
		initDomTracking();
		createStore();
		initializeLayout();
		initBlocks();
		initHooks();
		initTextHooks();
		setIsInitialized( true );
	}, [] );

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
		/>
	);
}
