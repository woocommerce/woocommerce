/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useMemo, useEffect } from '@wordpress/element';
import { SlotFillProvider, Spinner } from '@wordpress/components';
import { store as coreStore, Post } from '@wordpress/core-data';
import { CommandMenu, store as commandsStore } from '@wordpress/commands';
import { PluginArea } from '@wordpress/plugins';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	AutosaveMonitor,
	// @ts-expect-error Type is missing in @types/wordpress__editor
	LocalAutosaveMonitor,
	UnsavedChangesWarning,
	// @ts-expect-error Type is missing in @types/wordpress__editor
	EditorKeyboardShortcutsRegister,
	ErrorBoundary,
	PostLockedModal,
	store as editorStore,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { useNavigateToEntityRecord } from '../../hooks/use-navigate-to-entity-record';
import { Editor, FullscreenMode } from '../../private-apis';
import { useEmailCss } from '../../hooks';
import { PreviewSaveGuard } from '../preview/preview-save-guard';
import { TemplateSelection } from '../template-select';
import { StylesSidebar } from '../styles-sidebar';
import { SendPreview } from '../preview';
import { MoreMenu } from '../more-menu';
import { SettingsPanel } from '../sidebar/settings-panel';
import { TemplateSettingsPanel } from '../sidebar/template-settings-panel';
import { PublishSave } from '../../hacks/publish-save';
import { EditorNotices } from '../notices';
import { BlockCompatibilityWarnings } from '../sidebar';
import { BackButtonContent } from '../header/back-button-content';
import { recordEventOnce } from '../../events';

export function InnerEditor( {
	postId: initialPostId,
	postType: initialPostType,
	settings,
	contentRef,
} ) {
	const {
		currentPost,
		onNavigateToEntityRecord,
		onNavigateToPreviousEntityRecord,
	} = useNavigateToEntityRecord(
		// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
		initialPostId,
		// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
		initialPostType,
		'post-only'
	);

	const { post, template } = useSelect(
		( select ) => {
			const { getEntityRecord } = select( coreStore );
			const { getEditedPostTemplate } = select( storeName );
			const postObject = getEntityRecord(
				'postType',
				currentPost.postType,
				currentPost.postId
			) as Post | null;
			return {
				template:
					postObject && currentPost.postType !== 'wp_template'
						? getEditedPostTemplate( postObject.template )
						: null,
				post: postObject,
			};
		},
		[ currentPost.postType, currentPost.postId ]
	);

	// isFullScreenForced – comes from settings and cannot be changed by the user
	// isFullscreenEnabled – indicates if a user has enabled fullscreen mode
	const { isFullscreenEnabled, allCommands } = useSelect( ( select ) => {
		return {
			isFullscreenEnabled:
				select( storeName ).isFeatureActive( 'fullscreenMode' ),
			allCommands: select( commandsStore ).getCommands(),
		};
	}, [] );

	const { isFullScreenForced, displaySendEmailButton } = settings;

	// @ts-expect-error Type is missing in @types/wordpress__editor
	const { removeEditorPanel } = useDispatch( editorStore );
	useEffect( () => {
		removeEditorPanel( 'post-status' );
	}, [ removeEditorPanel ] );

	const [ styles ] = useEmailCss();

	const editorSettings = useMemo(
		// eslint-disable-next-line @typescript-eslint/no-unsafe-return
		() => ( {
			...settings,
			onNavigateToEntityRecord,
			onNavigateToPreviousEntityRecord,
			defaultRenderingMode:
				currentPost.postType === 'wp_template'
					? 'post-only'
					: 'template-locked',
			supportsTemplateMode: true,
			styles,
		} ),
		[
			settings,
			onNavigateToEntityRecord,
			onNavigateToPreviousEntityRecord,
			currentPost.postType,
			styles,
		]
	);
	const canRenderEditor =
		post &&
		( currentPost.postType === 'wp_template' ||
			post.template === template?.slug || // If the post has a template, check proper template is loaded.
			( ! post.template && template ) ); // If the post has no template, we render with the default template.

	if ( ! canRenderEditor ) {
		return (
			<div className="spinner-container">
				<Spinner style={ { width: '80px', height: '80px' } } />
			</div>
		);
	}

	recordEventOnce( 'editor_layout_loaded' );
	return (
		<SlotFillProvider>
			{ /* @ts-expect-error canCopyContent is missing in @types/wordpress__editor */ }
			<ErrorBoundary canCopyContent>
				{ /* The CommandMenu is not needed if the commands are registered. The CommandMenu can be removed after we drop support for WP 6.8. */ }
				{ ( ! allCommands || allCommands.length === 0 ) && (
					<CommandMenu />
				) }
				<Editor
					postId={ currentPost.postId }
					postType={ currentPost.postType }
					settings={ editorSettings }
					templateId={ template && template.id }
					contentRef={ contentRef }
					styles={ styles } // This is needed for BC for Gutenberg below v22
				>
					<AutosaveMonitor />
					<LocalAutosaveMonitor />
					<UnsavedChangesWarning />
					<EditorKeyboardShortcutsRegister />
					<PostLockedModal />
					<TemplateSelection />
					<StylesSidebar />
					<SendPreview />
					<PreviewSaveGuard />
					<FullscreenMode
						isActive={ isFullScreenForced || isFullscreenEnabled }
					/>
					{ ( isFullScreenForced || isFullscreenEnabled ) && (
						<BackButtonContent />
					) }
					{ ! isFullScreenForced && <MoreMenu /> }
					{ currentPost.postType === 'wp_template' ? (
						<TemplateSettingsPanel />
					) : (
						<SettingsPanel />
					) }
					{ displaySendEmailButton && <PublishSave /> }
					<EditorNotices />
					<BlockCompatibilityWarnings />
					<PluginArea scope="woocommerce-email-editor" />
				</Editor>
			</ErrorBoundary>
		</SlotFillProvider>
	);
}
