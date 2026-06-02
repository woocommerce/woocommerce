/// <reference path="./alignment-control.d.ts" />
/// <reference path="./alignment-toolbar.d.ts" />
/// <reference path="./autocomplete.d.ts" />
/// <reference path="./block-alignment-toolbar.d.ts" />
/// <reference path="./block-context.d.ts" />
/// <reference path="./block-controls.d.ts" />
/// <reference path="./block-edit.d.ts" />
/// <reference path="./block-editor-keyboard-shortcuts.d.ts" />
/// <reference path="./block-format-controls.d.ts" />
/// <reference path="./block-heading-level-dropdown.d.ts" />
/// <reference path="./block-icon.d.ts" />
/// <reference path="./block-inspector.d.ts" />
/// <reference path="./block-list.d.ts" />
/// <reference path="./block-mover.d.ts" />
/// <reference path="./block-navigation/dropdown.d.ts" />
/// <reference path="./block-selection-clearer.d.ts" />
/// <reference path="./block-settings-menu.d.ts" />
/// <reference path="./block-switcher/multi-blocks-switcher.d.ts" />
/// <reference path="./block-title.d.ts" />
/// <reference path="./block-toolbar.d.ts" />
/// <reference path="./block-vertical-alignment-toolbar.d.ts" />
/// <reference path="./button-block-appender.d.ts" />
/// <reference path="./color-palette/index.d.ts" />
/// <reference path="./color-palette/with-color-context.d.ts" />
/// <reference path="./colors.d.ts" />
/// <reference path="./contrast-checker.d.ts" />
/// <reference path="./copy-handler.d.ts" />
/// <reference path="./default-block-appender.d.ts" />
/// <reference path="./font-sizes.d.ts" />
/// <reference path="./inner-blocks.d.ts" />
/// <reference path="./inserter.d.ts" />
/// <reference path="./inspector-advanced-controls.d.ts" />
/// <reference path="./inspector-controls.d.ts" />
/// <reference path="./link-control.d.ts" />
/// <reference path="./media-placeholder.d.ts" />
/// <reference path="./media-upload/check.d.ts" />
/// <reference path="./media-upload/index.d.ts" />
/// <reference path="./multi-select-scroll-into-view.d.ts" />
/// <reference path="./navigable-toolbar.d.ts" />
/// <reference path="./observe-typing.d.ts" />
/// <reference path="./panel-color-settings.d.ts" />
/// <reference path="./plain-text.d.ts" />
/// <reference path="./preserve-scroll-in-reorder.d.ts" />
/// <reference path="./provider.d.ts" />
/// <reference path="./rich-text.d.ts" />
/// <reference path="./skip-to-selected-block.d.ts" />
/// <reference path="./url-input/button.d.ts" />
/// <reference path="./url-input/index.d.ts" />
/// <reference path="./url-popover.d.ts" />
/// <reference path="./use-block-bindings-utils.d.ts" />
/// <reference path="./use-block-edit-context.d.ts" />
/// <reference path="./use-block-editing-mode.d.ts" />
/// <reference path="./use-block-props.d.ts" />
/// <reference path="./use-settings.d.ts" />
/// <reference path="./warning.d.ts" />
/// <reference path="./writing-flow.d.ts" />

declare module '@wordpress/block-editor/components' {
	/**
	 * Block Creation Components
	 */
	export { default as AlignmentControl } from "@wordpress/block-editor/components/alignment-control";
	export { default as AlignmentToolbar } from "@wordpress/block-editor/components/alignment-toolbar";
	export { default as Autocomplete } from "@wordpress/block-editor/components/autocomplete";
	export { default as BlockAlignmentToolbar } from "@wordpress/block-editor/components/block-alignment-toolbar";
	export { default as BlockContextProvider } from "@wordpress/block-editor/components/block-context";
	export { default as BlockControls } from "@wordpress/block-editor/components/block-controls";
	export { default as BlockEdit } from "@wordpress/block-editor/components/block-edit";
	export { default as BlockFormatControls } from "@wordpress/block-editor/components/block-format-controls";
	export { default as HeadingLevelDropdown } from "@wordpress/block-editor/components/block-heading-level-dropdown";
	export { default as BlockIcon } from "@wordpress/block-editor/components/block-icon";
	export { default as BlockNavigationDropdown } from "@wordpress/block-editor/components/block-navigation/dropdown";
	export { default as BlockVerticalAlignmentToolbar } from "@wordpress/block-editor/components/block-vertical-alignment-toolbar";
	export { default as ButtonBlockAppender } from "@wordpress/block-editor/components/button-block-appender";
	export { default as ColorPalette } from "@wordpress/block-editor/components/color-palette";
	export { default as withColorContext } from "@wordpress/block-editor/components/color-palette/with-color-context";
	export * from "@wordpress/block-editor/components/colors";
	export { default as ContrastChecker } from "@wordpress/block-editor/components/contrast-checker";
	export * from "@wordpress/block-editor/components/font-sizes";
	export { default as InnerBlocks, useInnerBlocksProps } from "@wordpress/block-editor/components/inner-blocks";
	export { default as InspectorAdvancedControls } from "@wordpress/block-editor/components/inspector-advanced-controls";
	export { default as InspectorControls } from "@wordpress/block-editor/components/inspector-controls";
	export { default as LinkControl } from "@wordpress/block-editor/components/link-control";
	export { default as MediaPlaceholder } from "@wordpress/block-editor/components/media-placeholder";
	export { default as MediaUpload } from "@wordpress/block-editor/components/media-upload";
	export { default as MediaUploadCheck } from "@wordpress/block-editor/components/media-upload/check";
	export { default as PanelColorSettings } from "@wordpress/block-editor/components/panel-color-settings";
	export { default as PlainText } from "@wordpress/block-editor/components/plain-text";
	export { default as RichText, RichTextShortcut, RichTextToolbarButton } from "@wordpress/block-editor/components/rich-text";
	export { default as URLInput } from "@wordpress/block-editor/components/url-input";
	export { default as URLInputButton } from "@wordpress/block-editor/components/url-input/button";
	export { default as URLPopover } from "@wordpress/block-editor/components/url-popover";

	/**
	 * Content Related Components
	 */
	export { default as BlockEditorKeyboardShortcuts } from "@wordpress/block-editor/components/block-editor-keyboard-shortcuts";
	export { default as BlockInspector } from "@wordpress/block-editor/components/block-inspector";
	export { default as BlockList } from "@wordpress/block-editor/components/block-list";
	export { default as BlockMover } from "@wordpress/block-editor/components/block-mover";
	export { default as BlockSelectionClearer } from "@wordpress/block-editor/components/block-selection-clearer";
	export { default as BlockSettingsMenu } from "@wordpress/block-editor/components/block-settings-menu";
	export { default as MultiBlocksSwitcher } from "@wordpress/block-editor/components/block-switcher/multi-blocks-switcher";
	export { default as BlockTitle } from "@wordpress/block-editor/components/block-title";
	export { default as BlockToolbar } from "@wordpress/block-editor/components/block-toolbar";
	export { default as CopyHandler } from "@wordpress/block-editor/components/copy-handler";
	export { default as DefaultBlockAppender } from "@wordpress/block-editor/components/default-block-appender";
	export { default as Inserter } from "@wordpress/block-editor/components/inserter";
	export { default as MultiSelectScrollIntoView } from "@wordpress/block-editor/components/multi-select-scroll-into-view";
	export { default as NavigableToolbar } from "@wordpress/block-editor/components/navigable-toolbar";
	export { default as ObserveTyping } from "@wordpress/block-editor/components/observe-typing";
	export { default as PreserveScrollInReorder } from "@wordpress/block-editor/components/preserve-scroll-in-reorder";
	export { default as SkipToSelectedBlock } from "@wordpress/block-editor/components/skip-to-selected-block";
	export { default as Warning } from "@wordpress/block-editor/components/warning";
	export { default as WritingFlow } from "@wordpress/block-editor/components/writing-flow";

	/*
	 * State Related Components
	 */
	export { default as BlockEditorProvider } from "@wordpress/block-editor/components/provider";

	/*
	 * Hooks
	 */
	export { useBlockBindingsUtils } from "@wordpress/block-editor/components/use-block-bindings-utils";
	export { useBlockEditContext } from "@wordpress/block-editor/components/use-block-edit-context";
	export { useBlockEditingMode } from "@wordpress/block-editor/components/use-block-editing-mode";
	export { useBlockProps } from "@wordpress/block-editor/components/use-block-props";
	export { useSettings } from "@wordpress/block-editor/components/use-settings";
}
