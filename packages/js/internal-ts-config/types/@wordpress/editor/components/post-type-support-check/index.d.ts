declare module '@wordpress/editor/build-types/components/post-type-support-check' {
	export default PostTypeSupportCheck;
	/**
	 * A component which renders its own children only if the current editor post
	 * type supports one of the given `supportKeys` prop.
	 *
	 * @param {Object}            props             Props.
	 * @param {React.ReactNode}   props.children    Children to be rendered if post
	 *                                              type supports.
	 * @param {(string|string[])} props.supportKeys String or string array of keys
	 *                                              to test.
	 *
	 * @return {React.ReactNode} The component to be rendered.
	 */
	declare function PostTypeSupportCheck({ children, supportKeys }: {
	    children: React.ReactNode;
	    supportKeys: (string | string[]);
	}): React.ReactNode;
}
