declare module '@wordpress/editor/build-types/components/post-excerpt/plugin' {
	export default PluginPostExcerpt;
	/**
	 * Renders a post excerpt panel in the post sidebar.
	 *
	 * @param {Object}          props             Component properties.
	 * @param {string}          [props.className] An optional class name added to the row.
	 * @param {React.ReactNode} props.children    Children to be rendered.
	 *
	 * @example
	 * ```js
	 * // Using ES5 syntax
	 * var __ = wp.i18n.__;
	 * var PluginPostExcerpt = wp.editPost.__experimentalPluginPostExcerpt;
	 *
	 * function MyPluginPostExcerpt() {
	 * 	return React.createElement(
	 * 		PluginPostExcerpt,
	 * 		{
	 * 			className: 'my-plugin-post-excerpt',
	 * 		},
	 * 		__( 'Post excerpt custom content' )
	 * 	)
	 * }
	 * ```
	 *
	 * @example
	 * ```jsx
	 * // Using ESNext syntax
	 * import { __ } from '@wordpress/i18n';
	 * import { __experimentalPluginPostExcerpt as PluginPostExcerpt } from '@wordpress/edit-post';
	 *
	 * const MyPluginPostExcerpt = () => (
	 * 	<PluginPostExcerpt className="my-plugin-post-excerpt">
	 * 		{ __( 'Post excerpt custom content' ) }
	 * 	</PluginPostExcerpt>
	 * );
	 * ```
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	declare function PluginPostExcerpt({ children, className }: {
	    className?: string | undefined;
	    children: React.ReactNode;
	}): React.ReactNode;
	declare namespace PluginPostExcerpt {
	    export { Slot };
	}
	declare const Slot: {
	    (props: import("@wordpress/components/build-types/slot-fill/types").DistributiveOmit<import("@wordpress/components/build-types/slot-fill/types").SlotComponentProps, "name">): import("react").JSX.Element;
	    displayName: string;
	    __unstableName: import("@wordpress/components/build-types/slot-fill/types").SlotKey;
	};
}
