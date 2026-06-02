declare module '@wordpress/editor/build-types/components/plugin-post-publish-panel' {
	export default PluginPostPublishPanel;
	/**
	 * Renders provided content to the post-publish panel in the publish flow
	 * (side panel that opens after a user publishes the post).
	 *
	 * @param {Object}                props                                 Component properties.
	 * @param {string}                [props.className]                     An optional class name added to the panel.
	 * @param {string}                [props.title]                         Title displayed at the top of the panel.
	 * @param {boolean}               [props.initialOpen=false]             Whether to have the panel initially opened. When no title is provided it is always opened.
	 * @param {WPBlockTypeIconRender} [props.icon=inherits from the plugin] The [Dashicon](https://developer.wordpress.org/resource/dashicons/) icon slug string, or an SVG WP element, to be rendered when the sidebar is pinned to toolbar.
	 * @param {React.ReactNode}       props.children                        Children to be rendered
	 *
	 * @example
	 * ```jsx
	 * // Using ESNext syntax
	 * import { __ } from '@wordpress/i18n';
	 * import { PluginPostPublishPanel } from '@wordpress/editor';
	 *
	 * const MyPluginPostPublishPanel = () => (
	 * 	<PluginPostPublishPanel
	 * 		className="my-plugin-post-publish-panel"
	 * 		title={ __( 'My panel title' ) }
	 * 		initialOpen={ true }
	 * 	>
	 *         { __( 'My panel content' ) }
	 * 	</PluginPostPublishPanel>
	 * );
	 * ```
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	declare function PluginPostPublishPanel({ children, className, title, initialOpen, icon, }: {
	    className?: string | undefined;
	    title?: string | undefined;
	    initialOpen?: boolean | undefined;
	    icon?: any;
	    children: React.ReactNode;
	}): React.ReactNode;
	declare namespace PluginPostPublishPanel {
	    export { Slot };
	}
	declare const Slot: {
	    (props: import("@wordpress/components/build-types/slot-fill/types").DistributiveOmit<import("@wordpress/components/build-types/slot-fill/types").SlotComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	    __unstableName: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	};
}
