declare module '@wordpress/editor/build-types/components/provider' {
	/**
	 * This component establishes a new post editing context, and serves as the entry point for a new post editor (or post with template editor).
	 *
	 * It supports a large number of post types, including post, page, templates,
	 * custom post types, patterns, template parts.
	 *
	 * All modification and changes are performed to the `@wordpress/core-data` store.
	 *
	 * @param {Object}          props                      The component props.
	 * @param {Object}          [props.post]               The post object to edit. This is required.
	 * @param {Object}          [props.__unstableTemplate] The template object wrapper the edited post.
	 *                                                     This is optional and can only be used when the post type supports templates (like posts and pages).
	 * @param {Object}          [props.settings]           The settings object to use for the editor.
	 *                                                     This is optional and can be used to override the default settings.
	 * @param {React.ReactNode} [props.children]           Children elements for which the BlockEditorProvider context should apply.
	 *                                                     This is optional.
	 *
	 * @example
	 * ```jsx
	 * <EditorProvider
	 *   post={ post }
	 *   settings={ settings }
	 *   __unstableTemplate={ template }
	 * >
	 *   { children }
	 * </EditorProvider>
	 * ```
	 *
	 * @return {React.ReactNode} The rendered EditorProvider component.
	 */
	export function EditorProvider(props: {
	    post?: Object | undefined;
	    __unstableTemplate?: Object | undefined;
	    settings?: Object | undefined;
	    children?: React.ReactNode;
	}): React.ReactNode;
	/**
	 * This component provides the editor context and manages the state of the block editor.
	 *
	 * @param {Object}  props                                The component props.
	 * @param {Object}  props.post                           The post object.
	 * @param {Object}  props.settings                       The editor settings.
	 * @param {boolean} props.recovery                       Indicates if the editor is in recovery mode.
	 * @param {Array}   props.initialEdits                   The initial edits for the editor.
	 * @param {Object}  props.children                       The child components.
	 * @param {Object}  [props.BlockEditorProviderComponent] The block editor provider component to use. Defaults to ExperimentalBlockEditorProvider.
	 * @param {Object}  [props.__unstableTemplate]           The template object.
	 *
	 * @example
	 * ```jsx
	 * <ExperimentalEditorProvider
	 *   post={ post }
	 *   settings={ settings }
	 *   recovery={ recovery }
	 *   initialEdits={ initialEdits }
	 *   __unstableTemplate={ template }
	 * >
	 *   { children }
	 * </ExperimentalEditorProvider>
	 *
	 * @return {Object} The rendered ExperimentalEditorProvider component.
	 */
	export const ExperimentalEditorProvider: ({ useSubRegistry, ...props }: any) => import("react").JSX.Element;
	export default EditorProvider;
}
