/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useMemo, useEffect } from '@wordpress/element';
import { SlotFillProvider, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { CommandMenu } from '@wordpress/commands';
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
import { TemplateSelection } from '../template-select';
import { StylesSidebar } from '../styles-sidebar';
import { SendPreview } from '../preview';
import { MoreMenu } from '../more-menu';
import { SettingsPanel } from '../sidebar/settings-panel';
import { TemplateSettingsPanel } from '../sidebar/template-settings-panel';
import { PublishSave } from '../../hacks/publish-save';
import { EditorNotices } from '../notices';
import { BlockCompatibilityWarnings } from '../sidebar';

export function InnerEditor( {
	postId: initialPostId,
	postType: initialPostType,
	settings,
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

	const { post, template, isFullscreenEnabled } = useSelect(
		( select ) => {
			const { getEntityRecord } = select( coreStore );
			const { getEditedPostTemplate } = select( storeName );
			const postObject = getEntityRecord(
				'postType',
				currentPost.postType,
				currentPost.postId
			);
			return {
				template:
					currentPost.postType !== 'wp_template'
						? getEditedPostTemplate()
						: null,
				post: postObject,
				isFullscreenEnabled:
					select( storeName ).isFeatureActive( 'fullscreenMode' ),
			};
		},
		[ currentPost.postType, currentPost.postId ]
	);

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
		} ),
		[
			settings,
			onNavigateToEntityRecord,
			onNavigateToPreviousEntityRecord,
			currentPost.postType,
		]
	);

	if ( ! post || ( currentPost.postType !== 'wp_template' && ! template ) ) {
		return (
			<div className="spinner-container">
				<Spinner style={ { width: '80px', height: '80px' } } />
			</div>
		);
	}

	return (
		<SlotFillProvider>
			{ /* @ts-expect-error canCopyContent is missing in @types/wordpress__editor */ }
			<ErrorBoundary canCopyContent>
				<CommandMenu />
				<Editor
					postId={ currentPost.postId }
					postType={ currentPost.postType }
					settings={ editorSettings }
					templateId={ template && template.id }
					styles={ styles }
				>
					<AutosaveMonitor />
					<LocalAutosaveMonitor />
					<UnsavedChangesWarning />
					<EditorKeyboardShortcutsRegister />
					<PostLockedModal />
					<TemplateSelection />
					<StylesSidebar />
					<SendPreview />
					<FullscreenMode isActive={ isFullscreenEnabled } />
					<MoreMenu />
					{ currentPost.postType === 'wp_template' ? (
						<TemplateSettingsPanel />
					) : (
						<SettingsPanel />
					) }
					<PublishSave />
					<EditorNotices />
					<BlockCompatibilityWarnings />
				</Editor>
			</ErrorBoundary>
		</SlotFillProvider>
	);
}
