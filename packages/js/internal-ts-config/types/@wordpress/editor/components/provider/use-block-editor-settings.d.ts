declare module '@wordpress/editor/build-types/components/provider/use-block-editor-settings' {
	export default useBlockEditorSettings;
	/**
	 * React hook used to compute the block editor settings to use for the post editor.
	 *
	 * @param {Object} settings      EditorProvider settings prop.
	 * @param {string} postType      Editor root level post type.
	 * @param {string} postId        Editor root level post ID.
	 * @param {string} renderingMode Editor rendering mode.
	 *
	 * @return {Object} Block Editor Settings.
	 */
	declare function useBlockEditorSettings(settings: Object, postType: string, postId: string, renderingMode: string): Object;
}
