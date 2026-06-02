declare module '@wordpress/editor/build-types/components/post-trash/check' {
	/**
	 * Wrapper component that renders its children only if the post can be trashed.
	 *
	 * @param {Object}          props          The component props.
	 * @param {React.ReactNode} props.children The child components.
	 *
	 * @return {React.ReactNode} The rendered child components or null if the post can't be trashed.
	 */
	export default function PostTrashCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
