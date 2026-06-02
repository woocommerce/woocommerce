declare module '@wordpress/editor/build-types/components/plugin-document-setting-panel' {
	export default PluginDocumentSettingPanel;
	/**
	 * Renders items below the Status & Availability panel in the Document Sidebar.
	 *
	 * @param {Object}                props                                 Component properties.
	 * @param {string}                props.name                            Required. A machine-friendly name for the panel.
	 * @param {string}                [props.className]                     An optional class name added to the row.
	 * @param {string}                [props.title]                         The title of the panel
	 * @param {WPBlockTypeIconRender} [props.icon=inherits from the plugin] The [Dashicon](https://developer.wordpress.org/resource/dashicons/) icon slug string, or an SVG WP element, to be rendered when the sidebar is pinned to toolbar.
	 * @param {React.ReactNode}       props.children                        Children to be rendered
	 *
	 * @example
	 * ```js
	 * // Using ES5 syntax
	 * var el = React.createElement;
	 * var __ = wp.i18n.__;
	 * var registerPlugin = wp.plugins.registerPlugin;
	 * var PluginDocumentSettingPanel = wp.editor.PluginDocumentSettingPanel;
	 *
	 * function MyDocumentSettingPlugin() {
	 * 	return el(
	 * 		PluginDocumentSettingPanel,
	 * 		{
	 * 			className: 'my-document-setting-plugin',
	 * 			title: 'My Panel',
	 * 			name: 'my-panel',
	 * 		},
	 * 		__( 'My Document Setting Panel' )
	 * 	);
	 * }
	 *
	 * registerPlugin( 'my-document-setting-plugin', {
	 * 		render: MyDocumentSettingPlugin
	 * } );
	 * ```
	 *
	 * @example
	 * ```jsx
	 * // Using ESNext syntax
	 * import { registerPlugin } from '@wordpress/plugins';
	 * import { PluginDocumentSettingPanel } from '@wordpress/editor';
	 *
	 * const MyDocumentSettingTest = () => (
	 * 		<PluginDocumentSettingPanel className="my-document-setting-plugin" title="My Panel" name="my-panel">
	 *			<p>My Document Setting Panel</p>
	 *		</PluginDocumentSettingPanel>
	 *	);
	 *
	 *  registerPlugin( 'document-setting-test', { render: MyDocumentSettingTest } );
	 * ```
	 *
	 * @return {React.ReactNode} The component to be rendered.
	 */
	declare function PluginDocumentSettingPanel({ name, className, title, icon, children, }: {
	    name: string;
	    className?: string | undefined;
	    title?: string | undefined;
	    icon?: any;
	    children: React.ReactNode;
	}): React.ReactNode;
	declare namespace PluginDocumentSettingPanel {
	    export { Slot };
	}
	declare const Slot: {
	    (props: import("@wordpress/components/build-types/slot-fill/types").DistributiveOmit<import("@wordpress/components/build-types/slot-fill/types").SlotComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	    __unstableName: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	};
}
