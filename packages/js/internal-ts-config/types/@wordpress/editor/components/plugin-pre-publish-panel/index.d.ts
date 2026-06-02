declare module '@wordpress/editor/build-types/components/plugin-pre-publish-panel' {
	export default PluginPrePublishPanel;
	/**
	 * Renders provided content to the pre-publish side panel in the publish flow
	 * (side panel that opens when a user first pushes "Publish" from the main editor).
	 *
	 * @param {Object}                props                                 Component props.
	 * @param {string}                [props.className]                     An optional class name added to the panel.
	 * @param {string}                [props.title]                         Title displayed at the top of the panel.
	 * @param {boolean}               [props.initialOpen=false]             Whether to have the panel initially opened.
	 *                                                                      When no title is provided it is always opened.
	 * @param {WPBlockTypeIconRender} [props.icon=inherits from the plugin] The [Dashicon](https://developer.wordpress.org/resource/dashicons/)
	 *                                                                      icon slug string, or an SVG WP element, to be rendered when
	 *                                                                      the sidebar is pinned to toolbar. If `false` is passed, no icon will be rendered.
	 * @param {React.ReactNode}       props.children                        Children to be rendered
	 *
	 * @example
	 * ```jsx
	 * // Using ESNext syntax
	 * import { __ } from '@wordpress/i18n';
	 * import { PluginPrePublishPanel } from '@wordpress/editor';
	 *
	 * const MyPluginPrePublishPanel = () => (
	 * 	<PluginPrePublishPanel
	 * 		className="my-plugin-pre-publish-panel"
	 * 		title={ __( 'My panel title' ) }
	 * 		initialOpen={ true }
	 * 	>
	 * 	    { __( 'My panel content' ) }
	 * 	</PluginPrePublishPanel>
	 * );
	 * ```
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	declare function PluginPrePublishPanel({ children, className, title, initialOpen, icon, }: {
	    className?: string | undefined;
	    title?: string | undefined;
	    initialOpen?: boolean | undefined;
	    icon?: any;
	    children: React.ReactNode;
	}): React.ReactNode;
	declare namespace PluginPrePublishPanel {
	    export { Slot };
	}
	declare const Slot: {
	    (props: import("@wordpress/components/build-types/slot-fill/types").DistributiveOmit<import("@wordpress/components/build-types/slot-fill/types").SlotComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	    __unstableName: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	};
}
