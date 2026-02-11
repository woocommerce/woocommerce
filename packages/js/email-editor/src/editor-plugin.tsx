/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useSelect, useDispatch, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore -- fast-deep-equal has no type declarations
import deepEqual from 'fast-deep-equal';
import '@wordpress/format-library'; // Enables text formatting capabilities

/**
 * Internal dependencies
 */
import { getAllowedBlockNames, initBlocksForPlugin } from './blocks';
import { initializeLayout } from './layouts/flex-email';
import { createStore, storeName } from './store';
import { initTextHooks } from './text-hooks';
import {
	initEventCollector,
	initStoreTracking,
	initDomTracking,
} from './events';
import { initContentValidationMiddleware } from './middleware/content-validation';
import { initHacks } from './hacks';
import {
	useEmailCss,
	useContentValidation,
	useRemoveSavingFailedNotices,
} from './hooks';
import { FullscreenMode } from './private-apis';
import { BackButtonInnerButton } from './components/header/back-button-content';
import { getEditorConfigFromWindow } from './store/settings';

// @ts-expect-error __experimentalMainDashboardButton is not in types.
// eslint-disable-next-line @woocommerce/dependency-group
import { __experimentalMainDashboardButton as MainDashboardButton } from '@wordpress/edit-post';

/**
 * Basic editor plugin component.
 * Can be extended by integrators for custom plugin implementations.
 */
export function EditorPlugin() {
	return null;
}

/**
 * Email editor plugin component that runs inside the standard WordPress post editor.
 * Pushes email CSS to editor settings, restricts blocks, and renders email-specific UI.
 */
export function ExperimentEmailEditorPlugin() {
	const { isFullScreenForced } = useSelect(
		( sel ) => ( {
			isFullScreenForced:
				sel( storeName ).getInitialEditorSettings()
					?.isFullScreenForced ?? false,
		} ),
		[]
	);

	// Push email CSS styles to the WordPress editor settings
	const [ styles ] = useEmailCss();
	const prevStylesRef = useRef( styles );
	const { updateEditorSettings } = useDispatch( editorStore );

	useEffect( () => {
		if ( ! deepEqual( prevStylesRef.current, styles ) ) {
			prevStylesRef.current = styles;
			updateEditorSettings( { styles } );
		}
	}, [ styles, updateEditorSettings ] );

	// Set allowed block types for email editing
	useEffect( () => {
		updateEditorSettings( {
			allowedBlockTypes: getAllowedBlockNames(),
		} );
	}, [ updateEditorSettings ] );

	// Remove the post-status panel (not relevant for email editing)
	useEffect( () => {
		dispatch( 'core/edit-post' ).removeEditorPanel( 'post-status' );
	}, [] );

	// Run email-specific hooks
	useContentValidation();
	useRemoveSavingFailedNotices();

	return (
		<>
			{ isFullScreenForced && <FullscreenMode isActive /> }
			{ MainDashboardButton && (
				<MainDashboardButton>
					<BackButtonInnerButton />
				</MainDashboardButton>
			) }
		</>
	);
}

/**
 * Initialize the email editor plugin within the WordPress post editor.
 * This is the "batteries-included" init that registers the plugin and initializes all subsystems.
 * Call this before bootstrapping the WordPress editor via @wordpress/edit-post.
 */
export function experimentInitEmailEditorPlugin() {
	// Initialize all subsystems
	initEventCollector();
	initStoreTracking();
	initDomTracking();
	createStore();
	initContentValidationMiddleware();
	initBlocksForPlugin();
	initHacks();
	initTextHooks();
	initializeLayout();

	// Set configuration from window object
	const editorConfig = getEditorConfigFromWindow();
	dispatch( storeName ).setEditorConfig( editorConfig );

	// Set email post from window globals
	const { current_post_id, current_post_type } =
		window.WooCommerceEmailEditor;
	if ( current_post_id && current_post_type ) {
		dispatch( storeName ).setEmailPost(
			current_post_id,
			current_post_type
		);
	}

	// Register the plugin with WordPress
	registerPlugin( 'email-editor', {
		render: ExperimentEmailEditorPlugin,
	} );
}
