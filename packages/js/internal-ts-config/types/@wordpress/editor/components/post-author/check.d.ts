declare module '@wordpress/editor/build-types/components/post-author/check' {
	/**
	 * Wrapper component that renders its children only if the post type supports the author.
	 *
	 * @param {Object}          props          The component props.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} The component to be rendered. Return `null` if the post type doesn't
	 * supports the author or if there are no authors available.
	 */
	export default function PostAuthorCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
