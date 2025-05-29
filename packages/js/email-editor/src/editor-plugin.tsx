/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { store as editorStore } from '@wordpress/editor';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { createStore } from './store';

/**
 * Internal dependencies
 */
import { useEmailCss } from './hooks';
import { StylesSidebar } from './components/styles-sidebar';
import { TemplateSettingsPanel } from './components/sidebar/template-settings-panel';
import { SettingsPanel } from './components/sidebar/settings-panel';
import { BlockCompatibilityWarnings } from './components/sidebar';
import {
	initDomTracking,
	initEventCollector,
	initStoreTracking,
} from './events';
import { initializeLayout } from './layouts/flex-email';
import { initBlocksForPlugin } from './blocks';
import { initHooks } from './editor-hooks';
import { initTextHooks } from './text-hooks';


const EmailEditorPlugin = () => {
	const { updateEditorSettings } = useDispatch( editorStore );
	const [ styles ] = useEmailCss();
	const { editedPostId, currentPostType } = useSelect( ( sel ) => {
		return {
			editedPostId: sel( editorStore ).getCurrentPostId(),
			currentPostType: sel( editorStore ).getCurrentPostType(),
		};
	} );

	// Remove post status panel. We replace it by our own. The native one needs more customizations.
	// @ts-expect-error Type is missing in @types/wordpress__editor
	const { removeEditorPanel } = useDispatch( editorStore );
	useEffect( () => {
		removeEditorPanel( 'post-status' );
	}, [ removeEditorPanel ] );

	// Push email styles to editor settings.
	// Set styles directly to settings overwriting the automatically loaded theme styles
	useEffect( () => {
		if ( ! styles ) {
			return;
		}
		const editorSettings = select( editorStore ).getEditorSettings();
		updateEditorSettings( {
			...editorSettings,
			styles,
		} );
	}, [ styles, editedPostId, updateEditorSettings ] );

	return (
		<>
			<StylesSidebar />
			{ currentPostType === 'wp_template' ? (
				<TemplateSettingsPanel />
			) : (
				<SettingsPanel />
			) }
			<BlockCompatibilityWarnings />
		</>
	);
};

export function initializeEmailEditorPlugin() {
	registerPlugin( 'email-editor', {
		render: EmailEditorPlugin,
	} );
	createStore();
	initEventCollector();
	initStoreTracking();
	initDomTracking();
	initializeLayout();
	initBlocksForPlugin();
	initHooks();
	initTextHooks();
}
