/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	AutosaveMonitor,
	LocalAutosaveMonitor,
	UnsavedChangesWarning,
	EditorKeyboardShortcutsRegister,
	EditorSnackbars,
	ErrorBoundary,
	PostLockedModal,
	store as editorStore,
} from '@wordpress/editor';
import { useMemo, useEffect } from '@wordpress/element';
import { SlotFillProvider, Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';

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
				// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
				currentPost.postType,
				// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
				currentPost.postId
			);
			const isFullscreenEnabled =
				select( storeName ).isFeatureActive( 'fullscreenMode' );
			return {
				template:
					currentPost.postType !== 'wp_template'
						? getEditedPostTemplate()
						: null,
				post: postObject,
				isFullscreenEnabled,
			};
		},
		[ currentPost.postType, currentPost.postId ]
	);

	const { removeEditorPanel } = useDispatch( editorStore );
	useEffect( () => {
		removeEditorPanel( 'post-status' );
	}, [] );

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
			<ErrorBoundary canCopyContent>
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
					<EditorSnackbars />
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
				</Editor>
			</ErrorBoundary>
		</SlotFillProvider>
	);
}
