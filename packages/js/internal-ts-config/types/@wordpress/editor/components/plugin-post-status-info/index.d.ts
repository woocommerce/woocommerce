declare module '@wordpress/editor/build-types/components/plugin-post-status-info' {
	export default PluginPostStatusInfo;
	/**
	 * Renders a row in the Summary panel of the Document sidebar.
	 * It should be noted that this is named and implemented around the function it serves
	 * and not its location, which may change in future iterations.
	 *
	 * @param {Object}          props             Component properties.
	 * @param {string}          [props.className] An optional class name added to the row.
	 * @param {React.ReactNode} props.children    Children to be rendered.
	 *
	 * @example
	 * ```js
	 * // Using ES5 syntax
	 * var __ = wp.i18n.__;
	 * var PluginPostStatusInfo = wp.editor.PluginPostStatusInfo;
	 *
	 * function MyPluginPostStatusInfo() {
	 * 	return React.createElement(
	 * 		PluginPostStatusInfo,
	 * 		{
	 * 			className: 'my-plugin-post-status-info',
	 * 		},
	 * 		__( 'My post status info' )
	 * 	)
	 * }
	 * ```
	 *
	 * @example
	 * ```jsx
	 * // Using ESNext syntax
	 * import { __ } from '@wordpress/i18n';
	 * import { PluginPostStatusInfo } from '@wordpress/editor';
	 *
	 * const MyPluginPostStatusInfo = () => (
	 * 	<PluginPostStatusInfo
	 * 		className="my-plugin-post-status-info"
	 * 	>
	 * 		{ __( 'My post status info' ) }
	 * 	</PluginPostStatusInfo>
	 * );
	 * ```
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	declare function PluginPostStatusInfo({ children, className }: {
	    className?: string | undefined;
	    children: React.ReactNode;
	}): React.ReactNode;
	declare namespace PluginPostStatusInfo {
	    export { Slot };
	}
	declare const Slot: {
	    (props: import("@wordpress/components/build-types/slot-fill/types").DistributiveOmit<import("@wordpress/components/build-types/slot-fill/types").SlotComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	    __unstableName: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	};
}
