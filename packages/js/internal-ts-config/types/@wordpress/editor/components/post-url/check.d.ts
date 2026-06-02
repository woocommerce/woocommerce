declare module '@wordpress/editor/build-types/components/post-url/check' {
	/**
	 * Check if the post URL is valid and visible.
	 *
	 * @param {Object}          props          The component props.
	 * @param {React.ReactNode} props.children The child components.
	 *
	 * @return {React.ReactNode} The child components if the post URL is valid and visible, otherwise null.
	 */
	export default function PostURLCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
