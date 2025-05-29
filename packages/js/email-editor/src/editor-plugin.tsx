/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { store as editorStore } from '@wordpress/editor';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
	// @ts-expect-error Type is missing in @types/wordpress__edit-post
	__experimentalMainDashboardButton as MainDashboardButton,
} from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import { createStore, storeName } from './store';
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
import { SendPreview } from './components/preview';
import { TemplateSelection } from './components/template-select';
import { BackButtonInnerButton } from './components/header/back-button-content';
import { PublishSave } from './hacks/publish-save';

import './style.scss';

const EmailEditorPlugin = () => {
	const { updateEditorSettings } = useDispatch( editorStore );
	const [ styles ] = useEmailCss();
	const {
		editedPostId,
		currentPostType,
		isFullScreenForced,
		displaySendEmailButton,
	} = useSelect( ( sel ) => {
		const initialEditorSettings =
			sel( storeName ).getInitialEditorSettings();
		return {
			editedPostId: sel( editorStore ).getCurrentPostId(),
			currentPostType: sel( editorStore ).getCurrentPostType(),
			isFullScreenForced: initialEditorSettings.isFullScreenForced,
			displaySendEmailButton:
				initialEditorSettings.displaySendEmailButton,
		};
	}, [] );

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
			<SendPreview />
			<TemplateSelection />
			{ isFullScreenForced && (
				<MainDashboardButton>
					<BackButtonInnerButton />
				</MainDashboardButton>
			) }
			{ displaySendEmailButton && <PublishSave /> }
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
