/// <reference path="./autocompleters/index.d.ts" />
/// <reference path="./autosave-monitor/index.d.ts" />
/// <reference path="./block-settings-menu/plugin-block-settings-menu-item.d.ts" />
/// <reference path="./character-count/index.d.ts" />
/// <reference path="./deprecated.d.ts" />
/// <reference path="./document-bar/index.d.ts" />
/// <reference path="./document-outline/check.d.ts" />
/// <reference path="./document-outline/index.d.ts" />
/// <reference path="./editor-history/redo.d.ts" />
/// <reference path="./editor-history/undo.d.ts" />
/// <reference path="./editor-notices/index.d.ts" />
/// <reference path="./editor-snackbars/index.d.ts" />
/// <reference path="./entities-saved-states/hooks/use-is-dirty.d.ts" />
/// <reference path="./entities-saved-states/index.d.ts" />
/// <reference path="./error-boundary/index.d.ts" />
/// <reference path="./global-keyboard-shortcuts/index.d.ts" />
/// <reference path="./global-keyboard-shortcuts/register-shortcuts.d.ts" />
/// <reference path="./local-autosave-monitor/index.d.ts" />
/// <reference path="./page-attributes/check.d.ts" />
/// <reference path="./page-attributes/order.d.ts" />
/// <reference path="./page-attributes/panel.d.ts" />
/// <reference path="./page-attributes/parent.d.ts" />
/// <reference path="./plugin-document-setting-panel/index.d.ts" />
/// <reference path="./plugin-more-menu-item/index.d.ts" />
/// <reference path="./plugin-post-publish-panel/index.d.ts" />
/// <reference path="./plugin-post-status-info/index.d.ts" />
/// <reference path="./plugin-pre-publish-panel/index.d.ts" />
/// <reference path="./plugin-preview-menu-item/index.d.ts" />
/// <reference path="./plugin-sidebar-more-menu-item/index.d.ts" />
/// <reference path="./plugin-sidebar/index.d.ts" />
/// <reference path="./post-author/check.d.ts" />
/// <reference path="./post-author/index.d.ts" />
/// <reference path="./post-author/panel.d.ts" />
/// <reference path="./post-comments/index.d.ts" />
/// <reference path="./post-discussion/panel.d.ts" />
/// <reference path="./post-excerpt/check.d.ts" />
/// <reference path="./post-excerpt/index.d.ts" />
/// <reference path="./post-excerpt/panel.d.ts" />
/// <reference path="./post-featured-image/check.d.ts" />
/// <reference path="./post-featured-image/index.d.ts" />
/// <reference path="./post-featured-image/panel.d.ts" />
/// <reference path="./post-format/check.d.ts" />
/// <reference path="./post-format/index.d.ts" />
/// <reference path="./post-last-revision/check.d.ts" />
/// <reference path="./post-last-revision/index.d.ts" />
/// <reference path="./post-last-revision/panel.d.ts" />
/// <reference path="./post-locked-modal/index.d.ts" />
/// <reference path="./post-pending-status/check.d.ts" />
/// <reference path="./post-pending-status/index.d.ts" />
/// <reference path="./post-pingbacks/index.d.ts" />
/// <reference path="./post-preview-button/index.d.ts" />
/// <reference path="./post-publish-button/index.d.ts" />
/// <reference path="./post-publish-button/label.d.ts" />
/// <reference path="./post-publish-panel/index.d.ts" />
/// <reference path="./post-saved-state/index.d.ts" />
/// <reference path="./post-schedule/check.d.ts" />
/// <reference path="./post-schedule/index.d.ts" />
/// <reference path="./post-schedule/label.d.ts" />
/// <reference path="./post-schedule/panel.d.ts" />
/// <reference path="./post-sticky/check.d.ts" />
/// <reference path="./post-sticky/index.d.ts" />
/// <reference path="./post-switch-to-draft-button/index.d.ts" />
/// <reference path="./post-sync-status/index.d.ts" />
/// <reference path="./post-taxonomies/check.d.ts" />
/// <reference path="./post-taxonomies/flat-term-selector.d.ts" />
/// <reference path="./post-taxonomies/hierarchical-term-selector.d.ts" />
/// <reference path="./post-taxonomies/index.d.ts" />
/// <reference path="./post-taxonomies/panel.d.ts" />
/// <reference path="./post-template/classic-theme.d.ts" />
/// <reference path="./post-template/panel.d.ts" />
/// <reference path="./post-text-editor/index.d.ts" />
/// <reference path="./post-title/index.d.ts" />
/// <reference path="./post-title/post-title-raw.d.ts" />
/// <reference path="./post-trash/check.d.ts" />
/// <reference path="./post-trash/index.d.ts" />
/// <reference path="./post-type-support-check/index.d.ts" />
/// <reference path="./post-url/check.d.ts" />
/// <reference path="./post-url/index.d.ts" />
/// <reference path="./post-url/label.d.ts" />
/// <reference path="./post-url/panel.d.ts" />
/// <reference path="./post-visibility/check.d.ts" />
/// <reference path="./post-visibility/index.d.ts" />
/// <reference path="./post-visibility/label.d.ts" />
/// <reference path="./provider/index.d.ts" />
/// <reference path="./table-of-contents/index.d.ts" />
/// <reference path="./theme-support-check/index.d.ts" />
/// <reference path="./time-to-read/index.d.ts" />
/// <reference path="./unsaved-changes-warning/index.d.ts" />
/// <reference path="./word-count/index.d.ts" />

declare module '@wordpress/editor/build-types/components' {
	export * from "@wordpress/editor/build-types/components/autocompleters";
	export * from "@wordpress/editor/build-types/components/deprecated";
	export { default as AutosaveMonitor } from "@wordpress/editor/build-types/components/autosave-monitor";
	export { default as DocumentBar } from "@wordpress/editor/build-types/components/document-bar";
	export { default as DocumentOutline } from "@wordpress/editor/build-types/components/document-outline";
	export { default as DocumentOutlineCheck } from "@wordpress/editor/build-types/components/document-outline/check";
	export { EditorKeyboardShortcuts };
	export { default as EditorKeyboardShortcutsRegister } from "@wordpress/editor/build-types/components/global-keyboard-shortcuts/register-shortcuts";
	export { default as EditorHistoryRedo } from "@wordpress/editor/build-types/components/editor-history/redo";
	export { default as EditorHistoryUndo } from "@wordpress/editor/build-types/components/editor-history/undo";
	export { default as EditorNotices } from "@wordpress/editor/build-types/components/editor-notices";
	export { default as EditorSnackbars } from "@wordpress/editor/build-types/components/editor-snackbars";
	export { default as EntitiesSavedStates } from "@wordpress/editor/build-types/components/entities-saved-states";
	export { useIsDirty as useEntitiesSavedStatesIsDirty } from "@wordpress/editor/build-types/components/entities-saved-states/hooks/use-is-dirty";
	export { default as ErrorBoundary } from "@wordpress/editor/build-types/components/error-boundary";
	export { default as LocalAutosaveMonitor } from "@wordpress/editor/build-types/components/local-autosave-monitor";
	export { default as PageAttributesCheck } from "@wordpress/editor/build-types/components/page-attributes/check";
	export { default as PageAttributesOrder } from "@wordpress/editor/build-types/components/page-attributes/order";
	export { default as PageAttributesPanel } from "@wordpress/editor/build-types/components/page-attributes/panel";
	export { default as PageAttributesParent } from "@wordpress/editor/build-types/components/page-attributes/parent";
	export { default as PageTemplate } from "@wordpress/editor/build-types/components/post-template/classic-theme";
	export { default as PluginDocumentSettingPanel } from "@wordpress/editor/build-types/components/plugin-document-setting-panel";
	export { default as PluginBlockSettingsMenuItem } from "@wordpress/editor/build-types/components/block-settings-menu/plugin-block-settings-menu-item";
	export { default as PluginMoreMenuItem } from "@wordpress/editor/build-types/components/plugin-more-menu-item";
	export { default as PluginPostPublishPanel } from "@wordpress/editor/build-types/components/plugin-post-publish-panel";
	export { default as PluginPostStatusInfo } from "@wordpress/editor/build-types/components/plugin-post-status-info";
	export { default as PluginPrePublishPanel } from "@wordpress/editor/build-types/components/plugin-pre-publish-panel";
	export { default as PluginPreviewMenuItem } from "@wordpress/editor/build-types/components/plugin-preview-menu-item";
	export { default as PluginSidebar } from "@wordpress/editor/build-types/components/plugin-sidebar";
	export { default as PluginSidebarMoreMenuItem } from "@wordpress/editor/build-types/components/plugin-sidebar-more-menu-item";
	export { default as PostTemplatePanel } from "@wordpress/editor/build-types/components/post-template/panel";
	export { default as PostAuthor } from "@wordpress/editor/build-types/components/post-author";
	export { default as PostAuthorCheck } from "@wordpress/editor/build-types/components/post-author/check";
	export { default as PostAuthorPanel } from "@wordpress/editor/build-types/components/post-author/panel";
	export { default as PostComments } from "@wordpress/editor/build-types/components/post-comments";
	export { default as PostDiscussionPanel } from "@wordpress/editor/build-types/components/post-discussion/panel";
	export { default as PostExcerpt } from "@wordpress/editor/build-types/components/post-excerpt";
	export { default as PostExcerptCheck } from "@wordpress/editor/build-types/components/post-excerpt/check";
	export { default as PostExcerptPanel } from "@wordpress/editor/build-types/components/post-excerpt/panel";
	export { default as PostFeaturedImage } from "@wordpress/editor/build-types/components/post-featured-image";
	export { default as PostFeaturedImageCheck } from "@wordpress/editor/build-types/components/post-featured-image/check";
	export { default as PostFeaturedImagePanel } from "@wordpress/editor/build-types/components/post-featured-image/panel";
	export { default as PostFormat } from "@wordpress/editor/build-types/components/post-format";
	export { default as PostFormatCheck } from "@wordpress/editor/build-types/components/post-format/check";
	export { default as PostLastRevision } from "@wordpress/editor/build-types/components/post-last-revision";
	export { default as PostLastRevisionCheck } from "@wordpress/editor/build-types/components/post-last-revision/check";
	export { default as PostLastRevisionPanel } from "@wordpress/editor/build-types/components/post-last-revision/panel";
	export { default as PostLockedModal } from "@wordpress/editor/build-types/components/post-locked-modal";
	export { default as PostPendingStatus } from "@wordpress/editor/build-types/components/post-pending-status";
	export { default as PostPendingStatusCheck } from "@wordpress/editor/build-types/components/post-pending-status/check";
	export { default as PostPingbacks } from "@wordpress/editor/build-types/components/post-pingbacks";
	export { default as PostPreviewButton } from "@wordpress/editor/build-types/components/post-preview-button";
	export { default as PostPublishButton } from "@wordpress/editor/build-types/components/post-publish-button";
	export { default as PostPublishButtonLabel } from "@wordpress/editor/build-types/components/post-publish-button/label";
	export { default as PostPublishPanel } from "@wordpress/editor/build-types/components/post-publish-panel";
	export { default as PostSavedState } from "@wordpress/editor/build-types/components/post-saved-state";
	export { default as PostSchedule } from "@wordpress/editor/build-types/components/post-schedule";
	export { default as PostScheduleCheck } from "@wordpress/editor/build-types/components/post-schedule/check";
	export { default as PostSchedulePanel } from "@wordpress/editor/build-types/components/post-schedule/panel";
	export { default as PostSticky } from "@wordpress/editor/build-types/components/post-sticky";
	export { default as PostStickyCheck } from "@wordpress/editor/build-types/components/post-sticky/check";
	export { default as PostSwitchToDraftButton } from "@wordpress/editor/build-types/components/post-switch-to-draft-button";
	export { default as PostSyncStatus } from "@wordpress/editor/build-types/components/post-sync-status";
	export { default as PostTaxonomies } from "@wordpress/editor/build-types/components/post-taxonomies";
	export { FlatTermSelector as PostTaxonomiesFlatTermSelector } from "@wordpress/editor/build-types/components/post-taxonomies/flat-term-selector";
	export { HierarchicalTermSelector as PostTaxonomiesHierarchicalTermSelector } from "@wordpress/editor/build-types/components/post-taxonomies/hierarchical-term-selector";
	export { default as PostTaxonomiesCheck } from "@wordpress/editor/build-types/components/post-taxonomies/check";
	export { default as PostTaxonomiesPanel } from "@wordpress/editor/build-types/components/post-taxonomies/panel";
	export { default as PostTextEditor } from "@wordpress/editor/build-types/components/post-text-editor";
	export { default as PostTitle } from "@wordpress/editor/build-types/components/post-title";
	export { default as PostTitleRaw } from "@wordpress/editor/build-types/components/post-title/post-title-raw";
	export { default as PostTrash } from "@wordpress/editor/build-types/components/post-trash";
	export { default as PostTrashCheck } from "@wordpress/editor/build-types/components/post-trash/check";
	export { default as PostTypeSupportCheck } from "@wordpress/editor/build-types/components/post-type-support-check";
	export { default as PostURL } from "@wordpress/editor/build-types/components/post-url";
	export { default as PostURLCheck } from "@wordpress/editor/build-types/components/post-url/check";
	export { default as PostURLPanel } from "@wordpress/editor/build-types/components/post-url/panel";
	export { default as PostVisibility } from "@wordpress/editor/build-types/components/post-visibility";
	export { default as PostVisibilityCheck } from "@wordpress/editor/build-types/components/post-visibility/check";
	export { default as TableOfContents } from "@wordpress/editor/build-types/components/table-of-contents";
	export { default as ThemeSupportCheck } from "@wordpress/editor/build-types/components/theme-support-check";
	export { default as UnsavedChangesWarning } from "@wordpress/editor/build-types/components/unsaved-changes-warning";
	export { default as WordCount } from "@wordpress/editor/build-types/components/word-count";
	export { default as TimeToRead } from "@wordpress/editor/build-types/components/time-to-read";
	export { default as CharacterCount } from "@wordpress/editor/build-types/components/character-count";
	export { default as EditorProvider } from "@wordpress/editor/build-types/components/provider";
	/**
	 * Handles the keyboard shortcuts for the editor.
	 *
	 * It provides functionality for various keyboard shortcuts such as toggling editor mode,
	 * toggling distraction-free mode, undo/redo, saving the post, toggling list view,
	 * and toggling the sidebar.
	 */
	export const VisualEditorGlobalKeyboardShortcuts: typeof EditorKeyboardShortcuts;
	/**
	 * Handles the keyboard shortcuts for the editor.
	 *
	 * It provides functionality for various keyboard shortcuts such as toggling editor mode,
	 * toggling distraction-free mode, undo/redo, saving the post, toggling list view,
	 * and toggling the sidebar.
	 */
	export const TextEditorGlobalKeyboardShortcuts: typeof EditorKeyboardShortcuts;
	import EditorKeyboardShortcuts from '@wordpress/editor/build-types/components/global-keyboard-shortcuts';
	export { default as PostScheduleLabel, usePostScheduleLabel } from "@wordpress/editor/build-types/components/post-schedule/label";
	export { default as PostURLLabel, usePostURLLabel } from "@wordpress/editor/build-types/components/post-url/label";
	export { default as PostVisibilityLabel, usePostVisibilityLabel } from "@wordpress/editor/build-types/components/post-visibility/label";
}
