declare module '@wordpress/editor/build-types/components/post-sticky/check' {
	/**
	 * Wrapper component that renders its children only if post has a sticky action.
	 *
	 * @param {Object}          props          Props.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} The component to be rendered or null if post type is not 'post' or hasStickyAction is false.
	 */
	export default function PostStickyCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
